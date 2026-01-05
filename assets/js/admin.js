/**
 * GreenAudit WP Admin JavaScript
 * 
 * @package GreenAudit
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // === 1. Audit Form Spinner ===
    const auditForm = document.querySelector('form[method="post"]:not([name])');
    const auditSpinner = document.getElementById('greenaudit-spinner');

    if (auditForm && auditSpinner) {
        auditForm.addEventListener('submit', function () {
            // Prevent double-submit
            if (auditSpinner.classList.contains('is-active')) {
                return false;
            }
            auditSpinner.classList.add('is-active');
        });
    }

    // === 2. WebP Optimization (AJAX) ===
    const optimizeBtn = document.getElementById('optimize-images-btn');
    const optimizeForm = document.getElementById('optimize-images-form');

    if (optimizeBtn && optimizeForm) {
        optimizeBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const spinner = document.getElementById('optimize-spinner');
            const resultDiv = document.getElementById('optimize-result');

            if (!spinner || !resultDiv) return;

            // Prevent duplicate requests
            if (spinner.classList.contains('is-active')) {
                return;
            }

            spinner.classList.add('is-active');
            resultDiv.innerHTML = '';

            // Get nonce from hidden field (more secure than localized script)
            const nonceInput = optimizeForm.querySelector('input[name="_wpnonce"]');
            if (!nonceInput || !nonceInput.value) {
                console.error('GreenAudit: Missing nonce');
                spinner.classList.remove('is-active');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'greenaudit_optimize_images_ajax');
            formData.append('_wpnonce', nonceInput.value);

            fetch(ajaxurl || greenaudit.ajax_url, {  // `ajaxurl` is WP global
                method: 'POST',
                body: formData,
                credentials: 'same-origin'  // Security: only send cookies to same domain
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                spinner.classList.remove('is-active');

                const msg = document.createElement('div');
                if (data.success && data.data) {
                    msg.className = 'notice notice-success';
                    msg.innerHTML = `
                        <p><strong>✅ ${greenaudit_i18n.success}</strong> ${data.data.converted} ${greenaudit_i18n.images_converted}.</p>
                        <p><strong>${greenaudit_i18n.reduction}:</strong> ${data.data.reduction} ${greenaudit_i18n.co2_per_visit}</p>
                        <p><em>${greenaudit_i18n.webp_note}</em></p>
                    `;
                } else {
                    msg.className = 'notice notice-error';
                    msg.innerHTML = '<p>❌ ' + (data.data?.error || greenaudit_i18n.unknown_error) + '</p>';
                }
                resultDiv.appendChild(msg);

                // Scroll to result
                resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(function (err) {
                spinner.classList.remove('is-active');
                console.error('GreenAudit AJAX error:', err);

                const msg = document.createElement('div');
                msg.className = 'notice notice-error';
                msg.innerHTML = '<p>⚠️ ' + greenaudit_i18n.ajax_error + '</p>';
                if (document.getElementById('optimize-result')) {
                    document.getElementById('optimize-result').appendChild(msg);
                }
            });
        });
    }
});

// === Localized strings for i18n (future-ready) ===
// To be populated via wp_localize_script() in PHP
if (typeof greenaudit_i18n === 'undefined') {
    var greenaudit_i18n = {
        success: 'Success!',
        images_converted: 'images converted to WebP',
        reduction: 'Estimated CO₂ reduction',
        co2_per_visit: 'g CO₂/visit',
        webp_note: 'WebP files are saved alongside originals. Browsers that support WebP will use them automatically.',
        unknown_error: 'An unknown error occurred.',
        ajax_error: 'Network error. Please check your connection and try again.'
    };
}