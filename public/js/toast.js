function showToast(message, type) {
    type = type || 'success';
    const container = document.getElementById('toastContainer');
    const root = getComputedStyle(document.documentElement);
    function cssVar(name, fallback) {
        return root.getPropertyValue(name).trim() || fallback;
    }
    const colors = {
        success: { bg: cssVar('--color-dark', '#2C3947'), icon: 'bi-check-circle' },
        error: { bg: '#dc3545', icon: 'bi-x-circle' },
        warning: { bg: cssVar('--color-gold', '#C2A56D'), icon: 'bi-exclamation-triangle' },
    };
    const c = colors[type] || colors.success;

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center border-0';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.style.cssText = `background: ${c.bg}; color: #fff; border-radius: var(--radius-btn);`;
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi ${c.icon} fs-5"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function showConfirm(message) {
    return new Promise(resolve => {
        document.getElementById('confirmMessage').textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
        document.getElementById('confirmYes').onclick = function() {
            modal.hide();
            resolve(true);
        };
        document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => resolve(false), { once: true });
    });
}
