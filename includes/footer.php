<?php
// includes/footer.php - Common footer
?>
    <?php if (isset($current_user) && $current_user): ?>
                </main>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Page-specific JS -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo SITE_URL; ?>js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom Scripts -->
    <?php if (isset($custom_script)): ?>
        <script><?php echo $custom_script; ?></script>
    <?php endif; ?>
    
    <!-- Global Scripts -->
    <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Confirm before destructive actions
        document.addEventListener('click', function(e) {
            if (e.target.matches('a[data-confirm], button[data-confirm]')) {
                if (!confirm(e.target.dataset.confirm || 'Bạn có chắc chắn?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Handle form submissions with loading state
        document.addEventListener('submit', function(e) {
            var form = e.target;
            var submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn && !form.classList.contains('no-loading')) {
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý...';
                submitBtn.disabled = true;
                
                // Re-enable button if form submission fails
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });
    
    // Format numbers with thousand separators
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Format date from ISO string
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN');
    }
    
    // AJAX helper
    function ajaxRequest(url, data = {}, method = 'POST') {
        return new Promise(function(resolve, reject) {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error(xhr.statusText));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network error'));
            };
            
            xhr.send(JSON.stringify(data));
        });
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 position-fixed" 
             style="bottom: 20px; right: 20px; z-index: 1050;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Đã sao chép vào clipboard', 'success');
        }).catch(function(err) {
            showToast('Không thể sao chép: ' + err, 'error');
        });
    }
    </script>
    
    <!-- Google Analytics (optional) -->
    <?php if (defined('GA_TRACKING_ID') && GA_TRACKING_ID): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_TRACKING_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GA_TRACKING_ID; ?>');
    </script>
    <?php endif; ?>
</body>
</html>