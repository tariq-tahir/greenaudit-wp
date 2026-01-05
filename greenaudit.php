<?php
/**
 * Plugin Name: GreenAudit WP
 * Plugin URI: https://ecowebtools.org/greenaudit
 * Description: Measure, reduce, and report your website's carbon footprint — powered by the original Website Carbon Calculator methodology.
 * Version: 0.3
 * Author: Tariq Tahir
 * Author URI: https://ecowebtools.org
 * License: GPL-3.0+
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// ✅ DEFINE CONSTANTS FIRST — before any requires or classes
define('GREENAUDIT_VERSION', '0.3');
define('GREENAUDIT_PATH', plugin_dir_path(__FILE__));
define('GREENAUDIT_URL', plugin_dir_url(__FILE__));

// ✅ NOW load classes
require_once GREENAUDIT_PATH . 'admin/class-greenaudit-admin.php';
require_once GREENAUDIT_PATH . 'includes/class-carbon-api.php';
require_once GREENAUDIT_PATH . 'includes/class-pdf-report.php';
require_once GREENAUDIT_PATH . 'includes/class-diagnostic.php';

// Initialize
add_action('plugins_loaded', function() {
    if (is_admin()) {
        new GreenAudit_Admin();
    }
});