export const orderStatusLabels: Record<string, string> = {
    pending: 'Menunggu',
    confirmed: 'Dikonfirmasi',
    processed: 'Diproses',
    shipped: 'Dikirim',
    delivered: 'Diterima',
    cancelled: 'Batal',
};

export const orderStatusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending: 'secondary',
    confirmed: 'default',
    processed: 'default',
    shipped: 'default',
    delivered: 'default',
    cancelled: 'destructive',
};

export const paymentStatusLabels: Record<string, string> = {
    pending: 'Menunggu',
    paid: 'Lunas',
    failed: 'Gagal',
    expired: 'Kedaluwarsa',
};
