import { formatRupiah } from '@/lib/utils';

export type CourierOption = {
    optionKey: string;
    courierCode: string;
    courierName: string;
    courierServiceCode?: string | null;
    courierServiceName?: string | null;
    cost: number;
    etd?: string | null;
};

type Props = {
    options: CourierOption[];
    value: string;
    onChange: (option: CourierOption) => void;
    loading?: boolean;
    error?: string | null;
    disabled?: boolean;
    showService?: boolean;
};

export function CourierSelect({ options, value, onChange, loading, error, disabled, showService = false }: Props) {
    if (loading) {
        return <p className="text-sm text-muted-foreground">Memuat opsi pengiriman...</p>;
    }

    if (error) {
        return <p className="text-sm text-destructive">{error}</p>;
    }

    if (options.length === 0) {
        return <p className="text-sm text-muted-foreground">Pengiriman ke kota ini belum tersedia.</p>;
    }

    return (
        <div className="space-y-2">
            {options.map((opt) => {
                const label = showService && opt.courierServiceName
                    ? `${opt.courierName} — ${opt.courierServiceName}`
                    : opt.courierName;

                return (
                    <label
                        key={opt.optionKey}
                        className="flex min-w-0 cursor-pointer items-center justify-between gap-3 rounded-xl border p-3 text-sm transition-colors hover:bg-muted/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                    >
                        <span className="flex items-center gap-3 min-w-0">
                            <input
                                type="radio"
                                name="shipping_option"
                                value={opt.optionKey}
                                checked={value === opt.optionKey}
                                onChange={() => onChange(opt)}
                                disabled={disabled}
                            />
                            <span className="font-medium">{label}</span>
                        </span>
                        <span className="shrink-0 tabular-nums text-muted-foreground">
                            {formatRupiah(opt.cost)}
                            {opt.etd ? ` (~${opt.etd} hari)` : ''}
                        </span>
                    </label>
                );
            })}
        </div>
    );
}
