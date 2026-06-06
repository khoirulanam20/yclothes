import type { ConfigField } from '@/components/admin/configuration/ConfigurationFieldRenderer';

export type FieldGroup = {
    title?: string;
    fields: ConfigField[];
    layout: 'stack' | 'grid-2';
};

const SECTION_GROUPS: Record<string, { title: string; names: string[]; layout: 'stack' | 'grid-2' }[]> = {
    'general.design': [
        { title: 'Logo & Favicon', names: ['brand_logo', 'favicon'], layout: 'grid-2' },
        { title: 'Warna Tema', names: ['color_gold', 'color_accent'], layout: 'grid-2' },
        { title: 'Sosial Media', names: ['social_instagram', 'social_facebook', 'social_tiktok'], layout: 'grid-2' },
    ],
    'general.email_notifications': [
        { title: 'Penerima Admin', names: ['email_admin_recipients'], layout: 'stack' },
        {
            title: 'Email ke Admin',
            names: ['email_admin_new_order', 'email_admin_payment_submitted'],
            layout: 'stack',
        },
        {
            title: 'Email ke Pembeli',
            names: [
                'email_customer_order_created',
                'email_customer_invoice_on_created',
                'email_customer_invoice_on_paid',
                'send_email_on_payment_expired',
            ],
            layout: 'stack',
        },
        {
            title: 'Email Update Status ke Pembeli',
            names: [
                'email_customer_status_awaiting_verification',
                'email_customer_status_confirmed',
                'email_customer_status_processed',
                'email_customer_status_shipped',
                'email_customer_status_delivered',
                'email_customer_status_completed',
                'email_customer_status_cancelled',
            ],
            layout: 'stack',
        },
    ],
};

function groupTitleForType(type: string, field: ConfigField): string | undefined {
    if (type === 'image') return 'Gambar';
    if (type === 'color') return 'Warna';
    if (field.name.startsWith('social_')) return 'Sosial Media';
    return undefined;
}

function fieldBucket(field: ConfigField): string {
    if (field.type === 'image') return 'image';
    if (field.type === 'color') return 'color';
    if (field.name.startsWith('social_')) return 'social';
    if (field.type === 'boolean') return 'boolean';
    return 'default';
}

export function buildConfigurationFieldGroups(fields: ConfigField[], sectionKey: string): FieldGroup[] {
    const explicit = SECTION_GROUPS[sectionKey];
    if (explicit) {
        return explicit
            .map(({ title, names, layout }) => ({
                title,
                layout,
                fields: names
                    .map((name) => fields.find((f) => f.name === name))
                    .filter((f): f is ConfigField => f !== undefined),
            }))
            .filter((group) => group.fields.length > 0);
    }

    const groups: FieldGroup[] = [];
    let current: (FieldGroup & { bucket: string }) | null = null;

    for (const field of fields) {
        const bucket = fieldBucket(field);
        const layout = bucket === 'default' || bucket === 'boolean' ? 'stack' : 'grid-2';

        if (current && current.bucket === bucket && bucket !== 'default' && bucket !== 'boolean') {
            current.fields.push(field);
            continue;
        }

        current = {
            title: groupTitleForType(bucket, field),
            fields: [field],
            layout,
            bucket,
        };
        groups.push(current);
    }

    return groups;
}
