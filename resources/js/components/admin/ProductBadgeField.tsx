import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { contrastTextColor } from '@/lib/utils';

export type BadgePresetValue = 'none' | 'sale' | 'new' | 'hot' | 'custom';

const PRESET_DEFAULTS: Record<
    Exclude<BadgePresetValue, 'none'>,
    { label: string; color: string }
> = {
    sale: { label: 'Sale', color: '#DC2626' },
    new: { label: 'New', color: '#16A34A' },
    hot: { label: 'Hot', color: '#EA580C' },
    custom: { label: '', color: '#6366F1' },
};

type Props = {
    preset: BadgePresetValue;
    label: string;
    color: string;
    presetOptions: Record<string, string>;
    onChange: (values: { preset: BadgePresetValue; label: string; color: string }) => void;
    errors?: { badge?: string; badge_preset?: string; badge_color?: string };
};

export function ProductBadgeField({
    preset,
    label,
    color,
    presetOptions,
    onChange,
    errors,
}: Props) {
    const handlePresetChange = (nextPreset: BadgePresetValue) => {
        if (nextPreset === 'none') {
            onChange({ preset: 'none', label: '', color: '' });
            return;
        }

        const defaults = PRESET_DEFAULTS[nextPreset];
        onChange({
            preset: nextPreset,
            label: nextPreset === 'custom' ? label : defaults.label,
            color: defaults.color,
        });
    };

    const previewLabel =
        preset === 'none' ? null : preset === 'custom' ? label || 'Label kustom' : label || PRESET_DEFAULTS[preset].label;
    const previewColor = color || (preset !== 'none' ? PRESET_DEFAULTS[preset]?.color : undefined);

    return (
        <div className="space-y-3">
            <div>
                <Label htmlFor="badge_preset">Badge</Label>
                <select
                    id="badge_preset"
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={preset}
                    onChange={(e) => handlePresetChange(e.target.value as BadgePresetValue)}
                >
                    {Object.entries(presetOptions).map(([value, optionLabel]) => (
                        <option key={value} value={value}>
                            {optionLabel}
                        </option>
                    ))}
                </select>
                {errors?.badge_preset && (
                    <p className="mt-1 text-sm text-destructive">{errors.badge_preset}</p>
                )}
            </div>

            {preset !== 'none' && (
                <>
                    {preset === 'custom' && (
                        <div>
                            <Label htmlFor="badge_label">Label badge</Label>
                            <Input
                                id="badge_label"
                                value={label}
                                maxLength={50}
                                onChange={(e) => onChange({ preset, label: e.target.value, color })}
                            />
                            {errors?.badge && (
                                <p className="mt-1 text-sm text-destructive">{errors.badge}</p>
                            )}
                        </div>
                    )}

                    <div>
                        <Label htmlFor="badge_color">Warna badge</Label>
                        <div className="mt-1 flex items-center gap-3">
                            <input
                                id="badge_color"
                                type="color"
                                value={color || '#6366F1'}
                                onChange={(e) => onChange({ preset, label, color: e.target.value.toUpperCase() })}
                                className="h-10 w-14 cursor-pointer rounded border border-input bg-background p-1"
                            />
                            <Input
                                value={color}
                                placeholder="#DC2626"
                                maxLength={7}
                                onChange={(e) => onChange({ preset, label, color: e.target.value })}
                                className="max-w-[140px]"
                            />
                        </div>
                        {errors?.badge_color && (
                            <p className="mt-1 text-sm text-destructive">{errors.badge_color}</p>
                        )}
                    </div>

                    {previewLabel && previewColor && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-muted-foreground">Preview:</span>
                            <Badge
                                style={{
                                    backgroundColor: previewColor,
                                    color: contrastTextColor(previewColor),
                                    borderColor: 'transparent',
                                }}
                            >
                                {previewLabel}
                            </Badge>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
