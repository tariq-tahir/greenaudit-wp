<?php
// Security: Ensure WP_ADMIN is defined
if (!defined('WP_ADMIN')) {
    wp_die('Access denied.');
}
?>

<div class="wrap">
    <h1>🌿 GreenAudit WP</h1>
    
    <div class="notice notice-info">
        <p>
            <strong>About this tool:</strong> GreenAudit WP uses the original 
            <a href="https://www.websitecarbon.com" target="_blank">Website Carbon Calculator</a> 
            methodology by Wholegrain Digital — the first standardized approach to estimating website carbon emissions.
        </p>
        <p>
            💡 <em>The internet consumes 1,021 TWh/year — more than the entire United Kingdom. 
            Digital emissions now rival global aviation. Every optimization counts.</em>
        </p>
    </div>

    <div id="greenaudit-controls">
        <form method="post" style="display:inline-block;">
            <?php wp_nonce_field('greenaudit_run_nonce'); ?>
            <input type="hidden" name="run_audit" value="1" />
            <input type="submit" class="button button-primary" value="🔍 Run Carbon Audit" />
            <span class="spinner" id="greenaudit-spinner"></span>
        </form>
    </div>

    <?php
    // Handle audit run
    if (isset($_POST['run_audit'])) {
        check_admin_referer('greenaudit_run_nonce');
        
        $api = new GreenAudit_Carbon_API();
        $result = $api->get_score(home_url());
        
        if ($result && isset($result['carbon'])) {
            $carbon = $result['carbon'];
            $energy = $result['energy'];
            $source = $result['source'] === 'fallback' ? ' (fallback estimate)' : '';
            
            echo '<div id="greenaudit-results" class="notice notice-success" style="margin-top:20px;">';
            echo '<h2>✅ Audit Complete</h2>';
            echo '<p><strong>Website:</strong> ' . esc_url(home_url()) . '</p>';
            echo '<p><strong>Carbon per visit:</strong> <span style="font-weight:bold;color:#2E7D32;">' . $carbon . '</span> g CO₂' . $source . '</p>';
            echo '<p><strong>Energy per visit:</strong> <span style="font-weight:bold;color:#1976D2;">' . $energy . '</span> kWh</p>';
            echo '<p><em>Data via the original <a href="https://www.websitecarbon.com/methodology/" target="_blank">Website Carbon methodology</a> (Wholegrain Digital)</em></p>';
            
            // PDF button
            $pdf_url = wp_nonce_url(
                admin_url('admin-post.php?action=greenaudit_download_pdf'),
                'greenaudit_pdf_nonce'
            );
            echo '<a href="' . esc_url($pdf_url) . '" class="button button-secondary" style="margin-top:15px; display:inline-block;">📥 Download PDF Report</a>';
            
            echo '</div>';

            // ✅ NEW: Full Diagnostic Report
            $diagnostic = new GreenAudit_Diagnostic();
            $diagnostics = $diagnostic->run_all();

            echo '<div id="greenaudit-diagnostics" style="margin-top:30px;">';
            echo '<h2>🔍 Full Carbon Reduction Audit</h2>';
            echo '<p><em>Based on guidance from <a href="https://wholegraindigital.com" target="_blank">Wholegrain Digital</a> and the Website Carbon Calculator.</em></p>';
            
            foreach ($diagnostics as $key => $item) {
                $emoji = $item['pass'] ? '✅' : '❌';
                $color = $item['pass'] ? '#4CAF50' : '#F44336';
                $title = ucfirst(str_replace('_', ' ', $key));
                
                echo '<details open style="margin:10px 0; border:1px solid #ddd; border-radius:4px; padding:12px;">';
                echo "<summary style=\"font-weight:bold; color:{$color};\">{$emoji} {$title}: {$item['message']}</summary>";
                
                if (!$item['pass']) {
                    echo '<p><strong>How to Fix:</strong></p>';
                    echo '<ul>';
                    echo '<li><strong>Plugin:</strong> <a href="' . esc_url($item['fix']['url']) . '" target="_blank">' . esc_html($item['fix']['plugin']) . '</a></li>';
                    echo '<li><strong>Manual Fix:</strong> ' . $item['fix']['manual'] . '</li>';
                    echo '</ul>';
                    
                    // Special: WebP has optimization button
                    if ($key === 'webp' && $item['count'] > 0) {
                        $ajax_url = admin_url('admin-ajax.php');
                        $optimize_nonce = wp_create_nonce('greenaudit_optimize_images');
                        
                        echo '<form method="post" name="optimize_images" id="optimize-images-form" style="margin-top:10px;">';
                        echo '<input type="hidden" name="action" value="greenaudit_optimize_images_ajax" />';
                        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($optimize_nonce) . '" />';
                        echo '<span class="spinner" id="optimize-spinner" style="display:inline-block;margin-left:8px;"></span>';
                        echo '</form>';
                        echo '<div id="optimize-result"></div>';
                    }
                }
                
                echo '</details>';
            }
            
            echo '</div>';

        } else {
            echo '<div class="notice notice-error"><p>❌ Failed to retrieve carbon data. Please check your internet connection.</p></div>';
        }
    }
    ?>

</div>