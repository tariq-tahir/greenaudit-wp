<?php
class GreenAudit_Carbon_API {
    public function get_score($url) {
        $api_url = add_query_arg('url', urlencode($url), 'https://api.websitecarbon.com/b/');
        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'GreenAudit-WP/0.2'],
        ]);

        if (is_wp_error($response)) {
            return $this->get_fallback_score();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['statistics'])) {
            return $this->get_fallback_score();
        }

        return [
            'carbon' => $data['statistics']['adjustedCarbon'],
            'energy' => $data['statistics']['energy'],
            'source' => 'api'
        ];
    }

    private function get_fallback_score() {
        // Fallback: Static estimate (based on avg. site: 1.8 MB, 0.81 kWh/GB, 475 gCO2/kWh)
        $page_weight_kb = 1800; // avg
        $kwh_per_gb = 0.81;
        $grid_intensity = 475; // global avg gCO2/kWh

        $kwh = ($page_weight_kb / 1048576) * $kwh_per_gb;
        $co2 = $kwh * $grid_intensity;

        return [
            'carbon' => round($co2, 2),
            'energy' => round($kwh, 4),
            'source' => 'fallback'
        ];
    }
}