type Props = {
    appUrl: string;
    gateway: 'midtrans' | 'doku' | 'klikqris';
};

export function PaymentGatewayUrlsCard({ appUrl, gateway }: Props) {
    const base = appUrl.replace(/\/$/, '');

    const rows =
        gateway === 'midtrans'
            ? [
                  { label: 'Notification URL (webhook)', value: `${base}/midtrans/notification` },
                  { label: 'Payment finish (verifikasi client)', value: `${base}/order/payment-finish/{order_number}?token={access_token}` },
                  { label: 'Finish redirect (Snap)', value: 'Diatur otomatis ke halaman sukses pesanan' },
              ]
            : gateway === 'doku'
              ? [
                    { label: 'Notification URL (webhook)', value: `${base}/doku/notification` },
                    { label: 'Callback URL (return browser)', value: `${base}/order/doku-return/{order_number}?token={access_token}` },
                ]
              : [
                    { label: 'Notification URL (webhook)', value: `${base}/klikqris/notification` },
                    { label: 'Snap Payment', value: 'Dibuka otomatis dari halaman pembayaran setelah checkout' },
                ];

    return (
        <div className="rounded-lg border bg-muted/30 p-4 space-y-3">
            <div>
                <h3 className="text-sm font-semibold">URL Callback & Webhook</h3>
                <p className="text-xs text-muted-foreground mt-1">
                    Salin URL berikut ke dashboard {gateway === 'midtrans' ? 'Midtrans' : gateway === 'doku' ? 'DOKU' : 'KlikQRIS'}. Ganti {'{order_number}'} dan {'{access_token}'} dengan nilai dari pesanan.
                </p>
            </div>
            <dl className="space-y-2 text-sm">
                {rows.map((row) => (
                    <div key={row.label}>
                        <dt className="text-muted-foreground text-xs">{row.label}</dt>
                        <dd className="font-mono text-xs break-all mt-0.5">{row.value}</dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
