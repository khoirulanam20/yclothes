import { FieldLabel } from '@measured/puck';
import { LinkUrlField } from '@/components/admin/LinkUrlField';

export function cmsLinkUrlField(label: string) {
    return {
        type: 'custom' as const,
        label,
        render: ({
            field,
            value,
            onChange,
        }: {
            field: { label?: string };
            value: string;
            onChange: (value: string) => void;
        }) => (
            <FieldLabel label={field.label ?? label}>
                <LinkUrlField
                    label=""
                    value={value ?? ''}
                    onChange={onChange}
                />
            </FieldLabel>
        ),
    };
}
