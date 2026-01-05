<?php
class GreenAudit_Diagnostic {
    
    public function run_all() {
        return [
            'webp' => $this->check_webp(),
            'fonts' => $this->check_fonts(),
            'caching' => $this->check_caching(),
            'minify' => $this->check_minify(),
            'hosting' => $this->check_hosting(),
            'design' => $this->check_design(),
            'dark_mode' => $this->check_dark_mode()
        ];
    }
    
    private function check_webp() {
        // Count images that are not WebP
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $non_webp = 0;
        foreach ($images as $id) {
            $meta = wp_get_attachment_metadata($id);
            if (!$meta) continue;
            if (!isset($meta['file']) || pathinfo($meta['file'], PATHINFO_EXTENSION) !== 'webp') {
                $non_webp++;
            }
        }
        
        return [
            'pass' => $non_webp === 0,
            'count' => $non_webp,
            'message' => $non_webp > 0 ? "Found {$non_webp} images that can be optimized to WebP." : "All images are in WebP format.",
            'fix' => [
                'plugin' => 'WebP Express',
                'url' => 'https://wordpress.org/plugins/webp-express/',
                'manual' => 'Install WebP Express — it automatically serves WebP where supported.'
            ]
        ];
    }
    
    private function check_fonts() {
        // Simple: Check if Google Fonts are used (via <link> in head)
        $html = file_get_contents(home_url());
        $has_google_fonts = strpos($html, 'fonts.googleapis.com') !== false;
        
        return [
            'pass' => !$has_google_fonts,
            'message' => $has_google_fonts ? "Uses Google Fonts — consider system fonts for faster load." : "Uses system fonts — great for performance.",
            'fix' => [
                'plugin' => 'Autoptimize',
                'url' => 'https://wordpress.org/plugins/autoptimize/',
                'manual' => 'Replace Google Fonts with system fonts: <code>font-family: -apple-system, BlinkMacSystemFont, sans-serif;</code>'
            ]
        ];
    }
    
    private function check_caching() {
        // Check if cache headers are set
        $headers = get_headers(home_url());
        $has_cache = false;
        foreach ($headers as $header) {
            if (strpos($header, 'Cache-Control') !== false) {
                $has_cache = true;
                break;
            }
        }
        
        return [
            'pass' => $has_cache,
            'message' => $has_cache ? "Caching enabled — good for performance." : "Caching not detected — enable via plugin or .htaccess.",
            'fix' => [
                'plugin' => 'WP Super Cache',
                'url' => 'https://wordpress.org/plugins/wp-super-cache/',
                'manual' => 'Add to .htaccess: <code>ExpiresActive On</code><br><code>AddOutputFilterByType DEFLATE text/html</code>'
            ]
        ];
    }
    
    private function check_minify() {
        // Check if CSS/JS files are minified (simple heuristic)
        $html = file_get_contents(home_url());
        $is_minified = strpos($html, '  ') === false; // No double spaces
        
        return [
            'pass' => $is_minified,
            'message' => $is_minified ? "CSS/JS appear minified — good for performance." : "CSS/JS may not be minified — consider optimization.",
            'fix' => [
                'plugin' => 'Autoptimize',
                'url' => 'https://wordpress.org/plugins/autoptimize/',
                'manual' => 'Minify CSS/JS using online tools like https://csscompressor.com/'
            ]
        ];
    }
    
    private function check_hosting() {
        // Get IP → map to hosting provider (simplified)
        $ip = gethostbyname(parse_url(home_url(), PHP_URL_HOST));
        $green_hosts = ['greengeeks', 'kualo', 'a2hosting', 'siteground'];
        $is_green = false;
        
        foreach ($green_hosts as $host) {
            if (strpos($ip, $host) !== false) {
                $is_green = true;
                break;
            }
        }
        
        return [
            'pass' => $is_green,
            'message' => $is_green ? "Hosted on green-powered server — excellent!" : "Hosting may not use renewable energy — consider switching.",
            'fix' => [
                'plugin' => 'Green Web Hosting Checker',
                'url' => 'https://www.thegreenwebfoundation.org/green-web-check/',
                'manual' => 'Switch to 100% renewable hosts: <strong>GreenGeeks</strong>, <strong>Kualo</strong>, <strong>A2 Hosting (Green)</strong>'
            ]
        ];
    }
    
    private function check_design() {
        // Heuristic: Count JS libraries (jQuery, React, Vue)
        $html = file_get_contents(home_url());
        $js_libraries = ['jquery', 'react', 'vue', 'angular', 'ember'];
        $found = 0;
        foreach ($js_libraries as $lib) {
            if (strpos($html, $lib) !== false) {
                $found++;
            }
        }
        
        return [
            'pass' => $found <= 1,
            'message' => $found > 1 ? "Uses {$found} JavaScript frameworks — consider simplifying design." : "Design is lightweight — good for performance.",
            'fix' => [
                'plugin' => 'Asset CleanUp',
                'url' => 'https://wordpress.org/plugins/wp-asset-cleanup/',
                'manual' => 'Remove unused scripts/widgets — simplify design for speed.'
            ]
        ];
    }
    
    private function check_dark_mode() {
        // Check if dark mode is enabled (via CSS media query)
        $html = file_get_contents(home_url());
        $has_dark_mode = strpos($html, 'prefers-color-scheme: dark') !== false;
        
        return [
            'pass' => $has_dark_mode,
            'message' => $has_dark_mode ? "Dark mode enabled — saves energy on OLED screens." : "Dark mode not detected — consider adding for mobile users.",
            'fix' => [
                'plugin' => 'WP Dark Mode',
                'url' => 'https://wordpress.org/plugins/wp-dark-mode/',
                'manual' => 'Add to CSS: <code>@media (prefers-color-scheme: dark) { body { background: #121212; color: #fff; } }</code>'
            ]
        ];
    }
}