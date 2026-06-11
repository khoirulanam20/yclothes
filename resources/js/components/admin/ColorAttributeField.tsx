import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ColorEntry = { hex: string; name: string };

type Props = {
    id: string;
    colors: ColorEntry[];
    onChange: (colors: ColorEntry[]) => void;
};

const DEFAULT_HEX = '#000000';

function normalizeHex(hex: string): string {
    const trimmed = hex.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(trimmed)) {
        return trimmed;
    }
    if (/^[0-9A-Fa-f]{6}$/.test(trimmed)) {
        return `#${trimmed}`;
    }
    return DEFAULT_HEX;
}

export function ColorAttributeField({ id, colors, onChange }: Props) {
    const rows = colors.length > 0 ? colors : [{ hex: DEFAULT_HEX, name: '' }];

    const updateRow = (index: number, patch: Partial<ColorEntry>) => {
        const next = rows.map((row, i) => (i === index ? { ...row, ...patch } : row));
        onChange(next);
    };

    const removeRow = (index: number) => {
        const next = rows.filter((_, i) => i !== index);
        onChange(next.length > 0 ? next : [{ hex: DEFAULT_HEX, name: '' }]);
    };

    const addRow = () => {
        onChange([...rows, { hex: DEFAULT_HEX, name: '' }]);
    };

    return (
        <div className="space-y-3">
            {rows.map((color, index) => (
                <div key={index} className="flex items-center gap-2">
                    <div className="flex shrink-0 items-center gap-2">
                        <Label htmlFor={`${id}-color-${index}`} className="sr-only">
                            Warna {index + 1}
                        </Label>
                        <input
                            id={`${id}-color-${index}`}
                            type="color"
                            value={normalizeHex(color.hex)}
                            onChange={(e) => updateRow(index, { hex: e.target.value })}
                            className="size-10 cursor-pointer rounded-md border border-input bg-background p-0.5"
                        />
                    </div>
                    <Input
                        placeholder="Nama warna"
                        value={color.name}
                        onChange={(e) => updateRow(index, { name: e.target.value })}
                        className="flex-1"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="shrink-0 text-muted-foreground hover:text-destructive"
                        onClick={() => removeRow(index)}
                        disabled={rows.length === 1 && !color.name && color.hex === DEFAULT_HEX}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            ))}

            <Button type="button" variant="outline" size="sm" onClick={addRow}>
                <Plus className="mr-1 size-4" />
                Tambah Warna
            </Button>

            <p className="text-xs text-muted-foreground">
                Pilih warna dan isi nama untuk setiap varian warna produk.
            </p>
        </div>
    );
}
