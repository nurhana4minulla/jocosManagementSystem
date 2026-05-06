</main> <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/2.3.0/browser/overlayscrollbars.browser.es6.min.js"></script>

<script>
        // scrollbar :>
        document.addEventListener("DOMContentLoaded", function() {
            const { OverlayScrollbars } = OverlayScrollbarsGlobal;
            
            
            OverlayScrollbars(document.body, {
                scrollbars: {
                    theme: 'os-theme-dark', 
                    autoHide: 'leave',      
                    autoHideDelay: 300     
                }
            });

            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                OverlayScrollbars(sidebar, {
                    scrollbars: {
                        theme: 'os-theme-light', 
                        autoHide: 'leave'
                    }
                });
            }
        });
    </script>

    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1055;">
    <div id="globalToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="toastMessage" style="font-size: 0.9rem;"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// REUSABLE TOAST FUNCTION
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('globalToast');
    const toastBody = document.getElementById('toastMessage');
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    
    // Reset classes
    toastEl.className = 'toast align-items-center border-0 shadow-lg text-white';
    document.querySelector('#globalToast .btn-close').classList.add('btn-close-white');
    
    if (type === 'success') {
        toastEl.classList.add('bg-success');
        toastBody.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + message;
    } else if (type === 'danger' || type === 'error') {
        toastEl.classList.add('bg-danger');
        toastBody.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + message;
    } else if (type === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
        toastEl.classList.remove('text-white');
        document.querySelector('#globalToast .btn-close').classList.remove('btn-close-white');
        toastBody.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>' + message;
    }
    
    bsToast.show();
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('success') || urlParams.has('update_success')) {
        showToast('Employee profile saved successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname); 
    } else if (urlParams.has('delete_success')) {
        showToast('Moved to Recycle Bin.', 'warning');
        window.history.replaceState(null, null, window.location.pathname);
    } else if (urlParams.has('error')) {
        showToast('Error: ' + urlParams.get('error'), 'danger');
        window.history.replaceState(null, null, window.location.pathname);
    }
});
</script>
</body>
</html>