<?php
/**
 * GreenAudit Admin Dashboard View
 * 
 * @package GreenAudit
 */

// Security
if (!defined('WP_ADMIN') || !current_user_can('manage_options')) {
    wp_die(esc_html__('Access denied.', 'greenaudit'));
}

// Extract variables passed from controller
$audit_result = isset($audit_result) ? $audit_result : null;
$is_audit_run = isset($is_audit_run) ? $is_audit_run : false;
?>

<div class="wrap">
    <h1>🌿 <?php echo esc_html__('GreenAudit WP', 'greenaudit'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong><?php esc_html_e('About this tool:', 'greenaudit'); ?></strong>
            <?php 
            printf(
                /* translators: %s: Website Carbon Calculator link */
                wp_kses(
                    __('GreenAudit WP uses the original <a href="%s" target="_blank" rel="noopener">Website Carbon Calculator</a> methodology by Wholegrain Digital — the first standardized approach to estimating website carbon emissions.', 'greenaudit'),
                    [
                        'a' => ['href' => [], 'target' => [], 'rel' => []]
                    ]
                ),
                esc_url('https://www.websitecarbon.com')
            );
            ?>
        </p>
        <p>
            💡 <em>
            <?php 
            esc_html_e('The internet consumes 1,021 TWh/year — more than the entire United Kingdom. Digital emissions now rival global aviation. Every optimization counts.', 'greenaudit');
            ?>
            </em>
        </p>
    </div>

    <div id="greenaudit-controls">
        <form method="post" style="display:inline-block;">
            <?php wp_nonce_field('greenaudit_run_nonce'); ?>
            <input type="hidden" name="run_audit" value="1" />
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('🔍 Run Carbon Audit', 'greenaudit'); ?>" />
            <span class="spinner" id="greenaudit-spinner"></span>
        </form>
    </div>

    <?php if ($is_audit_run): ?>
        <?php if ($audit_result && isset($audit_result['carbon'])): ?>
            <?php
            $carbon = $audit_result['carbon'];
            $energy = $audit_result['energy'];
            $source = ($audit_result['source'] === 'fallback') ? ' (' . __('fallback estimate', 'greenaudit') . ')' : '';
            $host = wp_parse_url(home_url(), PHP_URL_HOST);
            ?>

            <div id="greenaudit-results" class="notice notice-success" style="margin-top:20px;">
                <h2>✅ <?php esc_html_e('Audit Complete', 'greenaudit'); ?></h2>
                <p><strong><?php esc_html_e('Website:', 'greenaudit'); ?></strong> <?php echo esc_url(home_url()); ?></p>
                <p>
                    <strong><?php esc_html_e('Carbon per visit:', 'greenaudit'); ?></strong> 
                    <span style="font-weight:bold;color:#2E7D32;"><?php echo esc_html($carbon); ?></span> 
                    <?php esc_html_e('g CO₂', 'greenaudit'); ?><?php echo esc_html($source); ?>
                </p>
                <p>
                    <strong><?php esc_html_e('Energy per visit:', 'greenaudit'); ?></strong> 
                    <span style="font-weight:bold;color:#1976D2;"><?php echo esc_html($energy); ?></span> 
                    <?php esc_html_e('kWh', 'greenaudit'); ?>
                </p>
                <p>
                    <em>
                    <?php 
                    printf(
                        /* translators: %s: Methodology link */
                        wp_kses(
                            __('Data via the original <a href="%s" target="_blank" rel="noopener">Website Carbon methodology</a> (Wholegrain Digital)', 'greenaudit'),
                            ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                        ),
                        esc_url('https://www.websitecarbon.com/methodology/')
                    );
                    ?>
                    </em>
                </p>

                <!-- PDF Download Button -->
                <?php
                $pdf_url = wp_nonce_url(
                    admin_url('admin-post.php?action=greenaudit_download_pdf'),
                    'greenaudit_pdf_nonce'
                );
                ?>
                <a href="<?php echo esc_url($pdf_url); ?>" class="button button-secondary" style="margin-top:15px; display:inline-block;">
                    📥 <?php esc_html_e('Download PDF Report', 'greenaudit'); ?>
                </a>
            </div>

            <!-- Full Diagnostic Report -->
            <?php
            $diagnostic = new GreenAudit_Diagnostic();
            $diagnostics = $diagnostic->run_all();
            ?>

            <div id="greenaudit-diagnostics" style="margin-top:30px;">
                <h2>🔍 <?php esc_html_e('Full Carbon Reduction Audit', 'greenaudit'); ?></h2>
                <p>
                    <em>
                    <?php 
                    printf(
                        /* translators: %s: Wholegrain Digital link */
                        wp_kses(
                            __('Based on guidance from <a href="%s" target="_blank" rel="noopener">Wholegrain Digital</a> and the Website Carbon Calculator.', 'greenaudit'),
                            ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                        ),
                        esc_url('https://wholegraindigital.com')
                    );
                    ?>
                    </em>
                </p>

                <?php foreach ($diagnostics as $key => $item): ?>
                    <?php
                    $emoji = $item['pass'] ? '✅' : '❌';
                    $color = $item['pass'] ? '#4CAF50' : '#F44336';
                    $title = ucfirst(str_replace('_', ' ', $key));
                    ?>

                    <details <?php echo $item['pass'] ? '' : 'open'; ?> style="margin:10px 0; border:1px solid #ddd; border-radius:4px; padding:12px;">
                        <summary style="font-weight:bold; color:<?php echo esc_attr($color); ?>;">
                            <?php echo esc_html($emoji); ?> 
                            <?php echo esc_html($title); ?>: 
                            <?php echo esc_html($item['message']); ?>
                        </summary>

                        <?php if (!$item['pass']): ?>
                            <p><strong><?php esc_html_e('How to Fix:', 'greenaudit'); ?></strong></p>
                            <ul>
                                <li>
                                    <strong><?php esc_html_e('Plugin:', 'greenaudit'); ?></strong> 
                                    <a href="<?php echo esc_url($item['fix']['url']); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html($item['fix']['plugin']); ?>
                                    </a>
                                </li>
                                <li>
                                    <strong><?php esc_html_e('Manual Fix:', 'greenaudit'); ?></strong> 
                                    <?php 
                                    // Allow limited HTML in manual fix (e.g., <code>, <a>)
                                    echo wp_kses(
                                        $item['fix']['manual'],
                                        [
                                            'code' => [],
                                            'a' => ['href' => [], 'target' => [], 'rel' => []],
                                            'strong' => [],
                                            'em' => []
                                        ]
                                    );
                                    ?>
                                </li>
                            </ul>

                            <!-- WebP Optimization Button -->
                            <?php if ($key === 'webp' && isset($item['count']) && $item['count'] > 0): ?>
                                <?php
                                $optimize_nonce = wp_create_nonce('greenaudit_optimize_images');
                                ?>
                                <form method="post" name="optimize_images" id="optimize-images-form" style="margin-top:10px;">
                                    <input type="hidden" name="action" value="greenaudit_optimize_images_ajax" />
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($optimize_nonce); ?>" />
                                    <button type="button" id="optimize-images-btn" class="button button-secondary">
                                        <?php esc_html_e('🔄 Optimize Images to WebP', 'greenaudit'); ?>
                                    </button>
                                    <span class="spinner" id="optimize-spinner" style="display:inline-block;margin-left:8px;"></span>
                                </form>
                                <div id="optimize-result" style="margin-top:8px;"></div>
                            <?php endif; ?>

                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="notice notice-error">
                <p>❌ <?php esc_html_e('Failed to retrieve carbon data. Please check your internet connection.', 'greenaudit'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>