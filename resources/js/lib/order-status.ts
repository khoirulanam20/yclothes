export const orderStatusLabels: Record<string, string> = {
    pending: 'Menunggu',
    awaiting_verification: 'Menunggu Verifikasi',
    confirmed: 'Dikonfirmasi',
    processed: 'Diproses',
    shipped: 'Dikirim',
    delivered: 'Diterima',
    completed: 'Selesai',
    return: 'Retur',
    cancelled: 'Batal',
};

export const orderStatusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending: 'secondary',
    awaiting_verification: 'secondary',
    confirmed: 'default',
    processed: 'default',
    shipped: 'default',
    delivered: 'default',
    completed: 'default',
    return: 'secondary',
    cancelled: 'destructive',
};

export const paymentStatusLabels: Record<string, string> = {
    pending: 'Menunggu',
    paid: 'Lunas',
    failed: 'Gagal',
    expired: 'Kedaluwarsa',
};

export const returnStatusLabels: Record<string, string> = {
    pending_review: 'Menunggu Review',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    awaiting_return_shipment: 'Menunggu Kirim Retur',
    return_in_transit: 'Retur Dalam Perjalanan',
    received: 'Barang Diterima',
    refunding: 'Proses Refund',
    replacing: 'Proses Ganti Barang',
    completed: 'Selesai',
};
