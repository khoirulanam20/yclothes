import { FieldLabel } from '@measured/puck';
import { CmsHtmlEditorField } from '@/components/admin/cms/CmsHtmlEditorField';

export function cmsHtmlField(label: string, modalMinHeight = 480, buttonLabel?: string) {
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
                <CmsHtmlEditorField
                    value={value ?? ''}
                    onChange={onChange}
                    dialogTitle={field.label ?? label}
                    buttonLabel={buttonLabel ?? `Edit ${field.label ?? label}`}
                    modalMinHeight={modalMinHeight}
                />
            </FieldLabel>
        ),
    };
}
