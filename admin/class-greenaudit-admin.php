<?php
/**
 * GreenAudit Admin Controller
 * 
 * @package GreenAudit
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GreenAudit_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_greenaudit_download_pdf', [$this, 'download_pdf']);
        add_action('wp_ajax_greenaudit_optimize_images_ajax', [$this, 'ajax_optimize_images']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue admin assets only on GreenAudit pages
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_greenaudit') {
            return;
        }

        wp_enqueue_style(
            'greenaudit-admin',
            GREENAUDIT_URL . 'assets/css/admin.css',
            [],
            GREENAUDIT_VERSION // ✅ Dynamic version
        );

        wp_enqueue_script(
            'greenaudit-admin',
            GREENAUDIT_URL . 'assets/js/admin.js',
            ['jquery'],
            GREENAUDIT_VERSION, // ✅ Dynamic version
            true
        );

        wp_localize_script('greenaudit-admin', 'greenaudit', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Register top-level admin menu
     */
    public function add_menu() {
        add_menu_page(
            __('GreenAudit WP', 'greenaudit'),
            __('GreenAudit', 'greenaudit'),
            'manage_options',
            'greenaudit',
            [$this, 'render_dashboard'],
            plugin_dir_url(__FILE__) . '../assets/images/greenaudit-icon.png',
            90
        );
    }

    /**
     * Render dashboard view
     */
    public function render_dashboard() {
        $audit_result = null;
        $is_audit_run = false;

        if (isset($_POST['run_audit'])) {
            check_admin_referer('greenaudit_run_nonce');
            $is_audit_run = true;

            try {
                $api = new GreenAudit_Carbon_API();
                $audit_result = $api->get_score(home_url());
            } catch (Exception $e) {
                error_log('GreenAudit: API error - ' . $e->getMessage());
            }
        }

        require GREENAUDIT_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Handle PDF download (using admin-post for large output)
     */
    public function download_pdf() {

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'greenaudit'), 403);
        }

        check_admin_referer('greenaudit_pdf_nonce');

        try {
            require_once GREENAUDIT_PATH . 'includes/class-carbon-api.php';
            require_once GREENAUDIT_PATH . 'includes/class-pdf-report.php';

            $api = new GreenAudit_Carbon_API();
            $result = $api->get_score(home_url());

            if (!$result || !isset($result['carbon'])) {
                wp_die(esc_html__('No carbon data available.', 'greenaudit'), 400);
            }

            $pdf = new GreenAudit_PDF_Report();
            $output = $pdf->generate($result);

            if (is_wp_error($output)) {
                wp_die(
                    sprintf(
                        /* translators: %s: Error message */
                        esc_html__('PDF Error: %s', 'greenaudit'),
                        esc_html($output->get_error_message())
                    ),
                    500
                );
            }

            // Secure filename
            $host = wp_parse_url(home_url(), PHP_URL_HOST);
            $safe_host = sanitize_file_name($host ?: 'website');
            $filename = "green-audit-{$safe_host}-" . gmdate('Y-m-d') . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $output;
            exit;

        } catch (Exception $e) {
            error_log('GreenAudit PDF error: ' . $e->getMessage());
            wp_die(esc_html__('An unexpected error occurred.', 'greenaudit'), 500);
        }
    }

    /**
     * AJAX handler for image optimization
     */
    public function ajax_optimize_images() {
        if (!wp_doing_ajax()) {
            wp_die();
        }

        check_ajax_referer('greenaudit_optimize_images');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'error' => esc_html__('Access denied.', 'greenaudit')
            ]);
        }

        try {
            require_once GREENAUDIT_PATH . 'includes/class-optimizer.php';
            $optimizer = new GreenAudit_Optimizer();
            $result = $optimizer->optimize_images();

            if (is_wp_error($result)) {
                wp_send_json_error([
                    'error' => esc_html($result->get_error_message())
                ]);
            }

            wp_send_json_success([
                'converted' => intval($result['converted']),
                'reduction' => floatval($result['reduction'])
            ]);

        } catch (Exception $e) {
            error_log('GreenAudit AJAX error: ' . $e->getMessage());
            wp_send_json_error([
                'error' => esc_html__('An unexpected error occurred.', 'greenaudit')
            ]);
        }
    }
}