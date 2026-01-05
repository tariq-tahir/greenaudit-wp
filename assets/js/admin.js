document.addEventListener('DOMContentLoaded', function() {
    // Audit form spinner
    const auditForm = document.querySelector('form[method="post"]:not([name])');
    const auditSpinner = document.getElementById('greenaudit-spinner');
    
    if (auditForm && auditSpinner) {
        auditForm.addEventListener('submit', function() {
            auditSpinner.classList.add('is-active');
        });
    }

    // AJAX Image Optimization
    const optimizeForm = document.getElementById('optimize-images-form');
    if (optimizeForm) {
        optimizeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const spinner = document.getElementById('optimize-spinner');
            const resultDiv = document.getElementById('optimize-result');
            if (spinner) spinner.classList.add('is-active');
            if (resultDiv) resultDiv.innerHTML = '';
            
            const formData = new FormData(optimizeForm);
            
            fetch(greenaudit.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (spinner) spinner.classList.remove('is-active');
                
                const msg = document.createElement('div');
                if (data.success) {
                    msg.className = 'notice notice-success';
                    msg.innerHTML = `
                        <p><strong>✅ Success!</strong> ${data.data.converted} images converted to WebP.</p>
                        <p><strong>Estimated CO₂ reduction:</strong> ${data.data.reduction} g CO₂/visit</p>
                        <p><em>WebP files are saved alongside originals. Browsers that support WebP will use them automatically.</em></p>
                    `;
                } else {
                    msg.className = 'notice notice-error';
                    msg.innerHTML = `<p>❌ ${data.data.error}</p>`;
                }
                if (resultDiv) resultDiv.appendChild(msg);
            })
            .catch(err => {
                if (spinner) spinner.classList.remove('is-active');
                console.error('AJAX error:', err);
                alert('An error occurred. Please check DevTools Console.');
            });
        });
    }
});