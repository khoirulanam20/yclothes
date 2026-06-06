type Props = {
    enabled: boolean;
    text: string;
    ctaLabel: string;
    bgColor: string;
    textColor: string;
    storeLocation: string;
    waNumber: string;
};

export function PromoBarPreview({
    enabled,
    text,
    ctaLabel,
    bgColor,
    textColor,
    storeLocation,
    waNumber,
}: Props) {
    const style = {
        backgroundColor: bgColor || undefined,
        color: textColor || undefined,
    };

    return (
        <div className="space-y-2">
            {!enabled && (
                <p className="text-sm text-muted-foreground">
                    Bar promo saat ini nonaktif — preview di bawah menampilkan tampilan jika diaktifkan.
                </p>
            )}
            <div
                className="rounded-md border overflow-hidden bg-promo-background text-promo-foreground text-center text-xs py-2 px-4"
                style={style}
                aria-hidden={!enabled}
            >
                <div className="flex justify-between items-center gap-2">
                    <span className="truncate">{storeLocation || 'Lokasi toko'}</span>
                    <span className="hidden sm:inline truncate">
                        {text || 'Teks promo akan tampil di sini'}
                    </span>
                    <span className="shrink-0 font-medium underline-offset-2">
                        {ctaLabel || 'Hubungi WA'}
                    </span>
                </div>
            </div>
            {waNumber ? (
                <p className="text-xs text-muted-foreground">
                    CTA mengarah ke WhatsApp: {waNumber}
                </p>
            ) : (
                <p className="text-xs text-amber-600">
                    Nomor WhatsApp belum diatur di Info Toko.
                </p>
            )}
        </div>
    );
}
