<?php
class GreenAudit_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_greenaudit_download_pdf', [$this, 'download_pdf']);
        add_action('wp_ajax_greenaudit_optimize_images_ajax', [$this, 'ajax_optimize_images']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']); // ← new
    }

    public function enqueue_assets($hook) {
        // Only load on GreenAudit pages
        if ($hook !== 'toplevel_page_greenaudit') return;
        
        // Enqueue CSS
        wp_enqueue_style(
            'greenaudit-admin',
            GREENAUDIT_URL . 'assets/css/admin.css',
            [],
            '0.3'
        );
        
        // Enqueue JS + localize ajax URL
        wp_enqueue_script(
            'greenaudit-admin',
            GREENAUDIT_URL . 'assets/js/admin.js',
            ['jquery'],
            '0.3',
            true
        );
        
        wp_localize_script('greenaudit-admin', 'greenaudit', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    public function add_menu() {
        add_menu_page(
            'GreenAudit WP',
            'GreenAudit',
            'manage_options',
            'greenaudit',
            [$this, 'render_dashboard'],
            plugin_dir_url(__FILE__) . '../assets/images/greenaudit-icon.png',
            90
        );
    }

    public function render_dashboard() {
        require GREENAUDIT_PATH . 'admin/views/dashboard.php';
    }

    public function download_pdf() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.', 403);
        }
        check_admin_referer('greenaudit_pdf_nonce');

        require_once GREENAUDIT_PATH . 'includes/class-carbon-api.php';
        require_once GREENAUDIT_PATH . 'includes/class-pdf-report.php';

        $api = new GreenAudit_Carbon_API();
        $result = $api->get_score(home_url());

        if (!$result || empty($result['carbon'])) {
            wp_die('No carbon data.', 400);
        }

        $pdf = new GreenAudit_PDF_Report();
        $output = $pdf->generate($result);

        if (is_wp_error($output)) {
            wp_die('PDF Error: ' . esc_html($output->get_error_message()), 500);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="green-audit-' . sanitize_title(wp_parse_url(home_url(), PHP_URL_HOST)) . '-' . date('Y-m-d') . '.pdf"');
        echo $output;
        exit;
    }


    public function ajax_optimize_images() {
        check_ajax_referer('greenaudit_optimize_images');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Access denied.']);
        }

        require_once GREENAUDIT_PATH . 'includes/class-optimizer.php';
        $optimizer = new GreenAudit_Optimizer();
        $result = $optimizer->optimize_images();

        if (is_wp_error($result)) {
            wp_send_json_error(['error' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'converted' => $result['converted'],
            'reduction' => $result['reduction']
        ]);
    }



}