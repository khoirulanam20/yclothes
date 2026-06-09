<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Pembayaran KlikQRIS — {{ site_app_name() }}</title>
    <link href="/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, sans-serif; background: #f8f9fa; margin: 0; }
        .pay-card { max-width: 480px; margin: 0 auto; }
        .qris-image { max-width: 220px; border-radius: 8px; border: 1px solid #dee2e6; background: #fff; padding: 8px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="pay-card text-center bg-white rounded-4 shadow-sm p-4 p-md-5">
        <h1 class="h4 fw-bold mb-2">Pembayaran KlikQRIS</h1>
        <p class="text-muted mb-1">Pesanan #{{ $order->order_number }}</p>
        @if($order->unique_payment_amount)
            <p class="fw-semibold text-primary mb-3">
                Nominal bayar: Rp {{ number_format($order->unique_payment_amount, 0, ',', '.') }}
            </p>
        @endif

        @php($qrisSrc = filled($qrisImage ?? null) ? $qrisImage : ($qrisUrl ?? null))
        @if($qrisSrc)
            <div class="mb-4">
                <p class="text-muted small mb-2">Scan QRIS di bawah lalu bayar sesuai nominal:</p>
                <img src="{{ $qrisSrc }}" alt="QRIS" class="qris-image" referrerpolicy="no-referrer">
            </div>
        @endif

        <button type="button" id="btnCheckStatus" class="btn btn-outline-secondary w-75 mx-auto mb-3">
            Cek Status
        </button>

        <p id="statusMessage" class="small text-muted mb-0" hidden></p>

        <div class="d-flex flex-column gap-2 mt-3">
            <a href="{{ $orderShowUrl }}" class="btn btn-link btn-sm">Lihat Detail Pesanan</a>
        </div>
    </div>
</div>

<script>
(function () {
    var verifyUrl = @json($verifyUrl);
    var csrfToken = @json(csrf_token());
    var isSandbox = @json($isSandbox);

    function redirectTop(url) {
        if (window.top && window.top !== window) {
            window.top.location.href = url;
        } else {
            window.location.href = url;
        }
    }

    function checkPaymentStatus() {
        var message = document.getElementById('statusMessage');
        var btn = document.getElementById('btnCheckStatus');

        if (message) {
            message.hidden = false;
            message.textContent = 'Memeriksa status pembayaran...';
        }
        if (btn) {
            btn.disabled = true;
        }

        fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({}),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.redirect) {
                    redirectTop(data.redirect);
                    return;
                }

                if (message) {
                    message.textContent = 'Pembayaran belum terkonfirmasi. Selesaikan pembayaran QRIS lalu coba lagi.';
                }
            })
            .catch(function () {
                if (message) {
                    message.textContent = 'Gagal memeriksa status. Silakan coba lagi.';
                }
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    document.getElementById('btnCheckStatus')?.addEventListener('click', checkPaymentStatus);

    var script = document.createElement('script');
    script.src = 'https://klikqris.com/js/payment-snap.js?' + (isSandbox ? 'env=sandbox&' : '') + 't=' + Date.now();
    script.onerror = function () {
        var message = document.getElementById('statusMessage');
        if (message) {
            message.hidden = false;
            message.textContent = 'Popup Snap gagal dimuat. Silakan scan QRIS di atas.';
        }
    };
    document.body.appendChild(script);
})();
</script>
</body>
</html>
