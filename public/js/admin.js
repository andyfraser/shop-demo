document.addEventListener('DOMContentLoaded', function() {
    // Batch Select All logic
    const selectAll = document.querySelector('.select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchBar = document.querySelector('.batch-bar');
    const selectedCountSpan = document.querySelector('.selected-count');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBatchBar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchBar);
    });

    function updateBatchBar() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const exportLink = document.querySelector('a[href*="/admin/orders/export"]');
        
        if (checked.length > 0) {
            batchBar?.classList.add('active');
            if (selectedCountSpan) {
                selectedCountSpan.textContent = checked.length;
            }
            
            // Update export link if it exists (on orders page)
            if (exportLink) {
                const ids = Array.from(checked).map(cb => cb.value);
                const baseUrl = exportLink.getAttribute('href').split('?')[0];
                const currentParams = new URLSearchParams(window.location.search);
                ids.forEach(id => currentParams.append('ids[]', id));
                exportLink.setAttribute('href', baseUrl + '?' + currentParams.toString());
            }
        } else {
            batchBar?.classList.remove('active');
            if (selectAll) selectAll.checked = false;
            
            // Reset export link to base URL with current filters
            if (exportLink) {
                const baseUrl = exportLink.getAttribute('href').split('?')[0];
                exportLink.setAttribute('href', baseUrl + window.location.search);
            }
        }
    }

    // Chart Modal logic
    const charts = document.querySelectorAll('.clickable-chart');
    const modal = document.getElementById('chartModal');
    const modalBody = document.getElementById('modalBody');
    const closeModal = document.getElementById('closeModal');

    if (modal && charts.length > 0) {
        charts.forEach(chart => {
            chart.addEventListener('click', function() {
                // Clone the SVG
                const clone = this.cloneNode(true);
                clone.classList.remove('clickable-chart');
                
                // Clear and append to modal
                modalBody.innerHTML = '';
                modalBody.appendChild(clone);
                
                // Show modal
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });

        const closeFunc = function() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        };

        closeModal?.addEventListener('click', closeFunc);
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeFunc();
            }
        });

        // Close on click outside content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeFunc();
            }
        });
    }
});
