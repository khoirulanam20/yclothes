import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';
import { AdminCheckboxRow } from '@/components/admin/AdminContent';
import { cn } from '@/lib/utils';

export type ConfigField = {
    name: string;
    title: string;
    type: string;
    value: string | number | boolean | null;
    url?: string | null;
    hasValue?: boolean;
    depends?: string | null;
    options?: { title: string; value: string }[] | null;
};

type Props = {
    field: ConfigField;
    data: Record<string, unknown>;
    errors: Record<string, string>;
    setData: (key: string, value: unknown) => void;
    compact?: boolean;
};

function isVisible(field: ConfigField, data: Record<string, unknown>): boolean {
    if (!field.depends) return true;
    const [depField, depValue] = field.depends.split(':');
    if (depField.endsWith('_enabled') || typeof data[depField] === 'boolean') {
        return Boolean(data[depField]) === (depValue === '1');
    }
    return String(data[depField] ?? '') === depValue;
}

function normalizeHex(value: string): string {
    if (!value) return '#000000';
    return value.startsWith('#') ? value : `#${value}`;
}

export function ConfigurationFieldRenderer({ field, data, errors, setData, compact = false }: Props) {
    if (!isVisible(field, data)) return null;

    const error = errors[field.name];

    if (field.type === 'boolean') {
        return (
            <AdminCheckboxRow
                id={field.name}
                label={field.title}
                checked={Boolean(data[field.name])}
                onChange={(checked) => setData(field.name, checked)}
            />
        );
    }

    if (field.type === 'textarea') {
        return (
            <div className="space-y-2">
                <Label htmlFor={field.name}>{field.title}</Label>
                <Textarea
                    id={field.name}
                    rows={4}
                    value={String(data[field.name] ?? '')}
                    onChange={(e) => setData(field.name, e.target.value)}
                />
                <FieldError message={error} />
            </div>
        );
    }

    if (field.type === 'multiselect' && field.options) {
        const selected = new Set(
            String(data[field.name] ?? '')
                .split(',')
                .map((v) => v.trim())
                .filter(Boolean),
        );

        const toggle = (value: string, checked: boolean) => {
            const next = new Set(selected);
            if (checked) {
                next.add(value);
            } else {
                next.delete(value);
            }
            setData(field.name, Array.from(next).join(','));
        };

        return (
            <div className="space-y-2">
                <Label>{field.title}</Label>
                <div className="grid gap-2 sm:grid-cols-2">
                    {field.options.map((opt) => (
                        <AdminCheckboxRow
                            key={opt.value}
                            id={`${field.name}_${opt.value}`}
                            label={opt.title}
                            checked={selected.has(opt.value)}
                            onChange={(checked) => toggle(opt.value, checked)}
                        />
                    ))}
                </div>
                <FieldError message={error} />
            </div>
        );
    }

    if (field.type === 'select' && field.options) {
        return (
            <div className="space-y-2">
                <Label htmlFor={field.name}>{field.title}</Label>
                <select
                    id={field.name}
                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    value={String(data[field.name] ?? '')}
                    onChange={(e) => setData(field.name, e.target.value)}
                >
                    {field.options.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.title}
                        </option>
                    ))}
                </select>
                <FieldError message={error} />
            </div>
        );
    }

    if (field.type === 'image') {
        const removeKey = `remove_${field.name}`;
        return (
            <div className={cn('space-y-3 rounded-lg border bg-muted/20 p-4', compact && 'h-full')}>
                <Label htmlFor={field.name} className="text-sm font-medium">{field.title}</Label>
                <div className="flex items-start gap-4">
                    <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-background">
                        {field.url ? (
                            <img src={field.url} alt="" className="max-h-full max-w-full object-contain p-1.5" />
                        ) : (
                            <span className="px-2 text-center text-xs text-muted-foreground">Belum ada</span>
                        )}
                    </div>
                    <div className="min-w-0 flex-1 space-y-2">
                        <Input
                            id={field.name}
                            type="file"
                            accept="image/*"
                            className="cursor-pointer text-sm file:mr-3"
                            onChange={(e) => setData(field.name, e.target.files?.[0] ?? null)}
                        />
                        {field.url && (
                            <AdminCheckboxRow
                                id={`remove_${field.name}`}
                                label="Hapus gambar saat ini"
                                checked={Boolean(data[removeKey])}
                                onChange={(checked) => setData(removeKey, checked)}
                                className="border-0 bg-transparent px-0 py-0 min-h-0"
                            />
                        )}
                    </div>
                </div>
                <FieldError message={error} />
            </div>
        );
    }

    if (field.type === 'color') {
        const raw = String(data[field.name] ?? '');
        const value = normalizeHex(raw);

        return (
            <div className={cn('space-y-2 rounded-lg border bg-muted/20 p-4', compact && 'h-full')}>
                <Label htmlFor={field.name} className="text-sm font-medium">{field.title}</Label>
                <div className="flex flex-wrap items-center gap-3">
                    <input
                        id={field.name}
                        type="color"
                        value={value}
                        onChange={(e) => setData(field.name, e.target.value)}
                        className="h-10 w-14 shrink-0 cursor-pointer rounded-md border border-input bg-background p-1"
                    />
                    <Input
                        type="text"
                        value={raw}
                        onChange={(e) => {
                            const next = e.target.value;
                            if (next === '' || /^#?[0-9A-Fa-f]{0,6}$/.test(next)) {
                                setData(field.name, next.startsWith('#') ? next : next ? `#${next}` : '');
                            }
                        }}
                        className="w-[6.5rem] font-mono text-sm uppercase"
                        placeholder="#FFFFFF"
                        maxLength={7}
                        aria-label={`Kode hex ${field.title}`}
                    />
                    <div
                        className="h-10 w-10 shrink-0 rounded-md border shadow-inner"
                        style={{ backgroundColor: value }}
                        role="img"
                        aria-label={`Pratinjau warna ${field.title}: ${value}`}
                    />
                </div>
                <FieldError message={error} />
            </div>
        );
    }

    if (field.type === 'password') {
        return (
            <div className="space-y-2">
                <Label htmlFor={field.name}>{field.title}</Label>
                {field.hasValue && (
                    <p className="text-xs text-muted-foreground">Sudah diset. Kosongkan jika tidak ingin mengubah.</p>
                )}
                <Input
                    id={field.name}
                    type="password"
                    autoComplete="new-password"
                    value={String(data[field.name] ?? '')}
                    onChange={(e) => setData(field.name, e.target.value)}
                />
                <FieldError message={error} />
            </div>
        );
    }

    const inputType = field.type === 'number' ? 'number' : 'text';

    return (
        <div className="space-y-2">
            <Label htmlFor={field.name}>{field.title}</Label>
            <Input
                id={field.name}
                type={inputType}
                value={data[field.name] === null || data[field.name] === undefined ? '' : String(data[field.name])}
                onChange={(e) =>
                    setData(
                        field.name,
                        field.type === 'number'
                            ? e.target.value === '' ? '' : Number(e.target.value)
                            : e.target.value,
                    )
                }
            />
            <FieldError message={error} />
        </div>
    );
}
