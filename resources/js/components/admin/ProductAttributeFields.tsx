import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';
import { ColorAttributeField } from '@/components/admin/ColorAttributeField';

export type AttributeDefinition = {
    id: number;
    code: string;
    name: string;
    type: string;
    isRequired?: boolean;
    isVariantAxis?: boolean;
    options?: { id: number; name: string }[];
};

type Props = {
    definitions: AttributeDefinition[];
    values: Record<string, unknown>;
    onChange: (code: string, value: unknown) => void;
    errors: Record<string, string>;
    productType?: string;
};

export function ProductAttributeFields({
    definitions,
    values,
    onChange,
    errors,
    productType,
}: Props) {
    if (definitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Keluarga atribut ini belum memiliki field. Tambahkan atribut di menu Keluarga Atribut.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {definitions.map((attr) => (
                <div key={attr.id}>
                    <div className="mb-1.5 flex items-center gap-2">
                        <Label htmlFor={`attr-${attr.code}`}>{attr.name}</Label>
                        {attr.isVariantAxis && productType === 'configurable' && (
                            <Badge variant="outline" className="text-xs">
                                Menghasilkan varian
                            </Badge>
                        )}
                    </div>

                    {renderField(attr, values[attr.code], (v) => onChange(attr.code, v))}

                    <FieldError message={errors[`attributes.${attr.code}`]} />
                </div>
            ))}
        </div>
    );
}

function renderField(
    attr: AttributeDefinition,
    rawValue: unknown,
    onChange: (value: unknown) => void,
) {
    if (attr.code === 'size' || attr.type === 'multiselect') {
        const selected = Array.isArray(rawValue) ? (rawValue as string[]) : [];
        const options = attr.options ?? [];

        return (
            <div className="flex flex-wrap gap-2">
                {options.map((opt) => {
                    const checked = selected.includes(opt.name);
                    return (
                        <label
                            key={opt.id}
                            className="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                checked={checked}
                                onChange={() => {
                                    onChange(
                                        checked
                                            ? selected.filter((s) => s !== opt.name)
                                            : [...selected, opt.name],
                                    );
                                }}
                            />
                            {opt.name}
                        </label>
                    );
                })}
            </div>
        );
    }

    if (attr.code === 'color') {
        const colors = Array.isArray(rawValue)
            ? (rawValue as { hex: string; name: string }[])
            : [];

        return (
            <ColorAttributeField
                id={`attr-${attr.code}`}
                colors={colors}
                onChange={onChange}
            />
        );
    }

    if (attr.type === 'select' && attr.options?.length) {
        return (
            <select
                id={`attr-${attr.code}`}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={(rawValue as string) ?? ''}
                onChange={(e) => onChange(e.target.value)}
            >
                <option value="">— Pilih —</option>
                {attr.options.map((opt) => (
                    <option key={opt.id} value={opt.name}>
                        {opt.name}
                    </option>
                ))}
            </select>
        );
    }

    if (attr.type === 'boolean') {
        return (
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={Boolean(rawValue)}
                    onChange={(e) => onChange(e.target.checked)}
                />
                Ya
            </label>
        );
    }

    if (attr.type === 'textarea') {
        return (
            <Textarea
                id={`attr-${attr.code}`}
                rows={3}
                value={(rawValue as string) ?? ''}
                onChange={(e) => onChange(e.target.value)}
            />
        );
    }

    if (attr.type === 'decimal' || attr.type === 'price') {
        return (
            <NumberInput
                id={`attr-${attr.code}`}
                value={rawValue === null || rawValue === undefined ? 0 : Number(rawValue)}
                onChange={(e) => onChange(e)}
            />
        );
    }

    return (
        <Input
            id={`attr-${attr.code}`}
            value={(rawValue as string) ?? ''}
            onChange={(e) => onChange(e.target.value)}
        />
    );
}
