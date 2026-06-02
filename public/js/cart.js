document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function addToCart(productId, size, color, qty, btn) {
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        const formData = {
            product_id: productId,
            qty: qty || 1,
            size: size || '',
            color: color || '',
        };
        const url = btn.dataset.url || '/cart/add';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cartCount').textContent = data.count;
                showToast(data.message, 'success');
            }
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function openVariantModal(btn) {
        const sizes = JSON.parse(btn.dataset.sizes || 'null');
        const colors = JSON.parse(btn.dataset.colors || 'null');
        const hasSizes = btn.dataset.hasSizes === 'true';
        const hasColors = btn.dataset.hasColors === 'true';

        document.getElementById('variantModalTitle').textContent = btn.dataset.name;
        document.getElementById('variantProductId').value = btn.dataset.id;

        const sizeWrap = document.getElementById('variantSizeWrap');
        const sizeOptions = document.getElementById('variantSizeOptions');
        const sizeInput = document.getElementById('variantSize');
        if (hasSizes && sizes) {
            sizeWrap.classList.remove('d-none');
            sizeOptions.innerHTML = '';
            sizes.forEach(function(s) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'size-toggle';
                b.textContent = s;
                b.dataset.value = s;
                b.onclick = function() {
                    sizeOptions.querySelectorAll('.size-toggle').forEach(function(x) { x.classList.remove('active'); });
                    b.classList.add('active');
                    sizeInput.value = b.dataset.value;
                };
                sizeOptions.appendChild(b);
            });
            sizeInput.value = '';
        } else {
            sizeWrap.classList.add('d-none');
            sizeInput.value = '';
        }

        const colorWrap = document.getElementById('variantColorWrap');
        const colorOptions = document.getElementById('variantColorOptions');
        const colorInput = document.getElementById('variantColor');
        if (hasColors && colors) {
            colorWrap.classList.remove('d-none');
            colorOptions.innerHTML = '';
            colors.forEach(function(c) {
                var hex = typeof c === 'string' ? c : c.hex;
                var name = typeof c === 'string' ? c : (c.name || c.hex);
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'color-swatch';
                b.dataset.value = hex;
                b.style.backgroundColor = hex;
                b.innerHTML = '<span class="color-label">' + name + '</span>';
                b.onclick = function() {
                    colorOptions.querySelectorAll('.color-swatch').forEach(function(x) { x.classList.remove('active'); });
                    b.classList.add('active');
                    colorInput.value = hex + '|' + name;
                };
                colorOptions.appendChild(b);
            });
            colorInput.value = '';
        } else {
            colorWrap.classList.add('d-none');
            colorInput.value = '';
        }

        document.getElementById('variantQtyDisplay').textContent = '1';

        var modal = new bootstrap.Modal(document.getElementById('variantModal'));
        modal.show();

        document.getElementById('variantAddBtn').onclick = function() {
            var selSize = document.getElementById('variantSize').value;
            var selColor = document.getElementById('variantColor').value;
            if (hasSizes && !selSize) {
                showToast('Silakan pilih ukuran terlebih dahulu', 'error');
                return;
            }
            if (hasColors && !selColor) {
                showToast('Silakan pilih warna terlebih dahulu', 'error');
                return;
            }
            var qty = parseInt(document.getElementById('variantQtyDisplay').textContent) || 1;
            addToCart(btn.dataset.id, selSize, selColor, qty, btn);
            modal.hide();
        };
    }

    document.querySelectorAll('.btn-add-cart').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var hasSizes = this.dataset.hasSizes === 'true';
            var hasColors = this.dataset.hasColors === 'true';
            if (hasSizes || hasColors) {
                openVariantModal(this);
            } else {
                addToCart(this.dataset.id, '', '', 1, this);
            }
        });
    });

    document.querySelectorAll('.btn-remove').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const confirmed = await showConfirm('Hapus item ini dari cart?');
            if (!confirmed) return;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            const url = this.dataset.url || '/cart/remove';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ key: this.dataset.key })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Item berhasil dihapus', 'success');
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(function() {
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });

    document.getElementById('variantQtyMinus').addEventListener('click', function() {
        var el = document.getElementById('variantQtyDisplay');
        var v = parseInt(el.textContent) || 1;
        el.textContent = Math.max(1, v - 1);
    });
    document.getElementById('variantQtyPlus').addEventListener('click', function() {
        var el = document.getElementById('variantQtyDisplay');
        var v = parseInt(el.textContent) || 1;
        el.textContent = v + 1;
    });
});
