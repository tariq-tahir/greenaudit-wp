<?php
/**
 * GreenAudit Carbon API Integration
 * 
 * Fetches carbon footprint data from the Website Carbon Calculator API
 * by Wholegrain Digital: https://www.websitecarbon.com
 * 
 * @package GreenAudit
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

if (!class_exists('GreenAudit_Carbon_API')) :

class GreenAudit_Carbon_API {

    /**
     * Base API endpoint
     * @var string
     */
    private $api_base_url = 'https://api.websitecarbon.com/b/';

    /**
     * Get carbon and energy metrics for a given URL
     *
     * @param string $url Full URL to audit (e.g. https://example.com)
     * @return array|WP_Error {
     *     @type float $carbon  CO₂ per page view (grams)
     *     @type float $energy  Energy per page view (kWh)
     *     @type string $source 'api' or 'fallback'
     * }
     */
    public function get_score($url) {
        $url = esc_url_raw($url);
        if (!$url) {
            return $this->get_fallback_score();
        }

        // Normalize URL (remove trailing slash, ensure scheme)
        $url = rtrim($url, '/');
        if (!wp_parse_url($url, PHP_URL_SCHEME)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $api_url = add_query_arg('url', urlencode($url), $this->api_base_url);

        $response = wp_remote_get($api_url, [
            'timeout'  => 15,
            'headers'  => [
                'User-Agent' => 'GreenAudit-WP/' . GREENAUDIT_VERSION . '; ' . home_url(),
                'Accept'     => 'application/json',
            ],
            'sslverify' => true, // Critical for security
        ]);

        // Handle network/HTTP errors
        if (is_wp_error($response)) {
            error_log('GreenAudit API error: ' . $response->get_error_message());
            return $this->get_fallback_score();
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Non-200 response → fallback
        if ($code !== 200) {
            error_log("GreenAudit API returned HTTP {$code}: {$body}");
            return $this->get_fallback_score();
        }

        $data = json_decode($body, true);

        // Validate API response structure
        if (
            !is_array($data) ||
            !isset($data['statistics']['adjustedCarbon']) ||
            !isset($data['statistics']['energy'])
        ) {
            error_log('GreenAudit: Invalid API response format');
            return $this->get_fallback_score();
        }

        return [
            'carbon' => floatval($data['statistics']['adjustedCarbon']),
            'energy' => floatval($data['statistics']['energy']),
            'source' => 'api',
        ];
    }

    /**
     * Fallback carbon estimation when API is unavailable
     * 
     * Based on 2024 global averages from:
     * - Website Carbon Calculator v3 methodology
     * - IEA grid intensity: ~475 gCO₂/kWh (global weighted)
     * - Avg page weight: ~1.8 MB (HTTP Archive)
     * 
     * @return array Fallback metrics
     */
    private function get_fallback_score() {
        // Configurable constants (update yearly if needed)
        $page_weight_bytes = 1800 * 1024; // 1.8 MB → bytes
        $kwh_per_gb         = 0.81;       // kWh per GB transferred (WCC methodology)
        $grid_intensity     = 475;        // gCO₂/kWh (global avg, source: IEA 2023)

        $kwh = ($page_weight_bytes / (1024 * 1024 * 1024)) * $kwh_per_gb; // GB → kWh
        $co2 = $kwh * $grid_intensity; // kWh × gCO₂/kWh = g CO₂

        return [
            'carbon' => round($co2, 2),      // e.g., 0.66 g CO₂
            'energy' => round($kwh, 5),      // e.g., 0.00139 kWh
            'source' => 'fallback',
        ];
    }
}

endif; // class_exists