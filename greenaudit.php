<?php
/**
 * GreenAudit WP
 *
 * @package     GreenAudit
 * @author      Tariq Tahir
 * @copyright   2026 Tariq Tahir
 * @license     GPL-3.0-or-later
 * @link        https://ecowebtools.org/greenaudit
 *
 * @wordpress-plugin
 * Plugin Name: GreenAudit WP
 * Plugin URI: https://ecowebtools.org/greenaudit
 * Description: Measure, reduce, and report your website's carbon footprint — based on the Website Carbon Calculator methodology.
 * Version: 1.0.0
 * Author: Tariq Tahir
 * Author URI: https://ecowebtools.org
 * License: GPL-3.0+
 * Text Domain: greenaudit
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define constants
define('GREENAUDIT_VERSION', '1.0.0');
define('GREENAUDIT_PATH', plugin_dir_path(__FILE__));
define('GREENAUDIT_URL', plugin_dir_url(__FILE__));

// Load text domain
function greenaudit_load_textdomain() {
    load_plugin_textdomain('greenaudit', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'greenaudit_load_textdomain');

// Enqueue admin assets
function greenaudit_enqueue_admin_assets() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_greenaudit') {
        wp_enqueue_style('greenaudit-admin-css', GREENAUDIT_URL . 'assets/css/admin.css', array(), GREENAUDIT_VERSION);
        wp_enqueue_script('greenaudit-admin-js', GREENAUDIT_URL . 'assets/js/admin.js', array('jquery'), GREENAUDIT_VERSION, true);
    }
}
add_action('admin_enqueue_scripts', 'greenaudit_enqueue_admin_assets');

// Load classes
require_once GREENAUDIT_PATH . 'admin/class-greenaudit-admin.php';
require_once GREENAUDIT_PATH . 'includes/class-carbon-api.php';
require_once GREENAUDIT_PATH . 'includes/class-pdf-report.php';
require_once GREENAUDIT_PATH . 'includes/class-diagnostic.php';

// Initialize
add_action('plugins_loaded', function() {
    if (is_admin() && current_user_can('manage_options')) {
        new GreenAudit_Admin();
    }
});