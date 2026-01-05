<?php
/**
 * GreenAudit Diagnostic Engine
 * 
 * Performs non-invasive, server-side checks for sustainability improvements.
 * 
 * @package GreenAudit
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

if (!class_exists('GreenAudit_Diagnostic')) :

class GreenAudit_Diagnostic {

    /**
     * Run all diagnostics
     * 
     * @return array Diagnostic results
     */
    public function run_all() {
        return [
            'webp'      => $this->check_webp(),
            'fonts'     => $this->check_fonts(),
            'caching'   => $this->check_caching(),
            'minify'    => $this->check_minify(),
            'hosting'   => $this->check_hosting(),
            'design'    => $this->check_design(),
            'dark_mode' => $this->check_dark_mode()
        ];
    }

    /**
     * Check if WebP is in use
     */
    private function check_webp() {
        $webp_plugin_active = class_exists('WebPExpress') || defined('WEBP_EXPRESS_VERSION');
        $webp_express_link = 'https://wordpress.org/plugins/webp-express/';

        if ($webp_plugin_active) {
            return [
                'pass'    => true,
                'count'   => 0,
                'message' => __('WebP conversion is active — great for performance.', 'greenaudit'),
                'fix'     => [
                    'plugin' => 'WebP Express',
                    'url'    => $webp_express_link,
                    'manual' => sprintf(
                        /* translators: %s: WebP Express URL */
                        __('WebP Express is already active. Ensure “Convert to WebP” is enabled in <a href="%s" target="_blank" rel="noopener">Settings</a>.', 'greenaudit'),
                        admin_url('options-general.php?page=webp-express')
                    )
                ]
            ];
        }

        // Count non-WebP images (safe, uses WP metadata)
        $non_webp = $this->count_non_webp_attachments();

        return [
            'pass'    => $non_webp === 0,
            'count'   => $non_webp,
            'message' => $non_webp > 0 
                ? sprintf(_n('Found %d image that can be optimized to WebP.', 'Found %d images that can be optimized to WebP.', $non_webp, 'greenaudit'), $non_webp)
                : __('All images are in WebP format.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'WebP Express',
                'url'    => $webp_express_link,
                'manual' => __('Install WebP Express — it automatically serves WebP where supported.', 'greenaudit')
            ]
        ];
    }

    /**
     * Count non-WebP media attachments
     * 
     * @return int Number of non-WebP images
     */
    private function count_non_webp_attachments() {
        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 200, // Avoid memory issues on large sites
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $non_webp = 0;
        foreach ($query->posts as $id) {
            $meta = wp_get_attachment_metadata($id);
            if (!$meta || empty($meta['file'])) {
                continue;
            }
            $ext = strtolower(pathinfo($meta['file'], PATHINFO_EXTENSION));
            if ($ext !== 'webp') {
                $non_webp++;
            }
        }

        return $non_webp;
    }

    /**
     * Check for Google Fonts usage
     */
    private function check_fonts() {
        // Check enqueued styles for Google Fonts
        $has_google_fonts = false;
        $wp_styles = wp_styles();
        if ($wp_styles instanceof WP_Styles) {
            foreach ($wp_styles->queue as $handle) {
                $src = $wp_styles->registered[$handle]->src ?? '';
                if (strpos($src, 'fonts.googleapis.com') !== false) {
                    $has_google_fonts = true;
                    break;
                }
            }
        }

        // Also check theme for @import
        $theme = wp_get_theme();
        $stylesheet = $theme->get_stylesheet_directory() . '/style.css';
        if (file_exists($stylesheet) && is_readable($stylesheet)) {
            $css = file_get_contents($stylesheet);
            if (strpos($css, 'fonts.googleapis.com') !== false) {
                $has_google_fonts = true;
            }
        }

        return [
            'pass'    => !$has_google_fonts,
            'message' => $has_google_fonts 
                ? __('Uses Google Fonts — consider system fonts for faster load.', 'greenaudit')
                : __('Uses system fonts — great for performance.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'Autoptimize',
                'url'    => 'https://wordpress.org/plugins/autoptimize/',
                'manual' => __('Replace Google Fonts with system fonts: <code>font-family: -apple-system, BlinkMacSystemFont, sans-serif;</code>', 'greenaudit')
            ]
        ];
    }

    /**
     * Check caching (via plugin detection or headers in buffer)
     */
    private function check_caching() {
        // Check for common caching plugins
        $caching_plugins = [
            'wp-super-cache/wp-cache.php',
            'w3-total-cache/w3-total-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'lite-speed-cache/litespeed-cache.php',
        ];

        foreach ($caching_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return [
                    'pass'    => true,
                    'message' => __('Caching enabled via plugin — good for performance.', 'greenaudit'),
                    'fix'     => [
                        'plugin' => 'WP Super Cache',
                        'url'    => 'https://wordpress.org/plugins/wp-super-cache/',
                        'manual' => __('Caching is active. Review settings in plugin dashboard.', 'greenaudit')
                    ]
                ];
            }
        }

        // Fallback: Check if server likely supports caching (e.g., Apache mod_expires)
        $mod_expires = in_array('mod_expires', apache_get_modules() ?? []); // Only on Apache

        return [
            'pass'    => $mod_expires,
            'message' => $mod_expires
                ? __('Server-level caching detected — good for performance.', 'greenaudit')
                : __('No caching plugin or server config detected — enable for faster load.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'WP Super Cache',
                'url'    => 'https://wordpress.org/plugins/wp-super-cache/',
                'manual' => __('Add to .htaccess:<br><code>ExpiresActive On<br>ExpiresByType text/css "access plus 1 year"<br>AddOutputFilterByType DEFLATE text/html</code>', 'greenaudit')
            ]
        ];
    }

    /**
     * Check minification (via plugin detection)
     */
    private function check_minify() {
        $minify_plugins = [
            'autoptimize/autoptimize.php',
            'wp-rocket/wp-rocket.php',
            'fast-velocity-minify/fvm.php',
        ];

        foreach ($minify_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return [
                    'pass'    => true,
                    'message' => __('CSS/JS minification active — good for performance.', 'greenaudit'),
                    'fix'     => [
                        'plugin' => 'Autoptimize',
                        'url'    => 'https://wordpress.org/plugins/autoptimize/',
                        'manual' => __('Minification is active. Review settings in plugin dashboard.', 'greenaudit')
                    ]
                ];
            }
        }

        return [
            'pass'    => false,
            'message' => __('No minification plugin detected — consider optimization.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'Autoptimize',
                'url'    => 'https://wordpress.org/plugins/autoptimize/',
                'manual' => __('Use Autoptimize to minify CSS/JS — improves load time and CO₂ footprint.', 'greenaudit')
            ]
        ];
    }

    /**
     * Check green hosting via Green Web Foundation API
     */
    private function check_hosting() {
        $host = parse_url(home_url(), PHP_URL_HOST);
        if (!$host) {
            return $this->default_hosting_result();
        }

        // Use Green Web Foundation API (free, public, accurate)
        $api_url = add_query_arg('url', urlencode($host), 'https://api.thegreenwebfoundation.org/greencheck/v3/');
        $response = wp_remote_get($api_url, [
            'timeout' => 5,
            'headers' => ['User-Agent' => 'GreenAudit-WP/' . GREENAUDIT_VERSION],
        ]);

        if (is_wp_error($response)) {
            return $this->default_hosting_result();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data) || empty($data['green'])) {
            return $this->default_hosting_result();
        }

        $is_green = $data['green'];

        return [
            'pass'    => $is_green,
            'message' => $is_green
                ? __('Hosted on 100% renewable energy — excellent!', 'greenaudit')
                : __('Hosting not verified as green — consider switching.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'Green Web Hosting Checker',
                'url'    => 'https://www.thegreenwebfoundation.org/green-web-check/',
                'manual' => __('Switch to certified green hosts: <strong>GreenGeeks</strong>, <strong>Kualo</strong>, <strong>A2 Hosting (Green)</strong>', 'greenaudit')
            ]
        ];
    }

    private function default_hosting_result() {
        return [
            'pass'    => false,
            'message' => __('Unable to verify green hosting — manual check recommended.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'Green Web Hosting Checker',
                'url'    => 'https://www.thegreenwebfoundation.org/green-web-check/',
                'manual' => __('Check your host at <a href="https://www.thegreenwebfoundation.org/green-web-check/" target="_blank" rel="noopener">Green Web Check</a>.', 'greenaudit')
            ]
        ];
    }

    /**
     * Check for heavy JS frameworks
     */
    private function check_design() {
        $heavy_scripts = [
            'jquery'   => false,
            'react'    => false,
            'vue'      => false,
            'angular'  => false,
            'ember'    => false,
            'backbone' => false,
        ];

        $wp_scripts = wp_scripts();
        if ($wp_scripts instanceof WP_Scripts) {
            foreach ($wp_scripts->queue as $handle) {
                $src = $wp_scripts->registered[$handle]->src ?? '';
                foreach (array_keys($heavy_scripts) as $lib) {
                    if (strpos($src, $lib) !== false) {
                        $heavy_scripts[$lib] = true;
                    }
                }
            }
        }

        $count = array_sum($heavy_scripts);

        return [
            'pass'    => $count <= 1,
            'message' => $count > 1
                ? sprintf(_n('Uses %d JavaScript framework — consider simplifying design.', 'Uses %d JavaScript frameworks — consider simplifying design.', $count, 'greenaudit'), $count)
                : __('Design is lightweight — good for performance and emissions.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'Asset CleanUp',
                'url'    => 'https://wordpress.org/plugins/wp-asset-cleanup/',
                'manual' => __('Dequeue unused scripts in <em>Settings → Asset CleanUp</em>. Simplify design for lower energy use.', 'greenaudit')
            ]
        ];
    }

    /**
     * Check for dark mode support
     */
    private function check_dark_mode() {
        // Check if dark mode plugin is active
        $dark_mode_active = is_plugin_active('wp-dark-mode/wp-dark-mode.php');

        if ($dark_mode_active) {
            return [
                'pass'    => true,
                'message' => __('Dark mode plugin active — saves energy on OLED screens.', 'greenaudit'),
                'fix'     => [
                    'plugin' => 'WP Dark Mode',
                    'url'    => 'https://wordpress.org/plugins/wp-dark-mode/',
                    'manual' => __('Dark mode is enabled. Test on mobile devices.', 'greenaudit')
                ]
            ];
        }

        // Check theme for prefers-color-scheme
        $theme = wp_get_theme();
        $stylesheet = $theme->get_stylesheet_directory() . '/style.css';
        $has_dark_mode = false;

        if (file_exists($stylesheet) && is_readable($stylesheet)) {
            $css = file_get_contents($stylesheet);
            $has_dark_mode = strpos($css, 'prefers-color-scheme: dark') !== false;
        }

        return [
            'pass'    => $has_dark_mode,
            'message' => $has_dark_mode
                ? __('Dark mode CSS detected — great for mobile energy savings.', 'greenaudit')
                : __('No dark mode detected — consider adding for OLED screens.', 'greenaudit'),
            'fix'     => [
                'plugin' => 'WP Dark Mode',
                'url'    => 'https://wordpress.org/plugins/wp-dark-mode/',
                'manual' => __('Add to CSS:<br><code>@media (prefers-color-scheme: dark) { body { background: #121212; color: #fff; } }</code>', 'greenaudit')
            ]
        ];
    }
}

endif; // class_exists