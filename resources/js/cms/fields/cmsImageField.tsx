import { FieldLabel } from '@measured/puck';
import { CmsImageUploadField } from '@/components/admin/cms/CmsImageUploadField';

export function cmsImageField(label: string) {
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
                <CmsImageUploadField value={value ?? ''} onChange={onChange} />
            </FieldLabel>
        ),
    };
}
