export type ButtonProps = {
    label: string;
    href: string;
    variant: 'primary' | 'outline';
};

export function ButtonBlock({ label, href, variant }: ButtonProps) {
    const className =
        variant === 'outline'
            ? 'inline-flex items-center justify-center rounded-md border px-6 py-2 text-sm font-medium hover:bg-muted'
            : 'inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground px-6 py-2 text-sm font-medium hover:opacity-90';

    const url = href?.trim() || '#';

    return (
        <div className="container mx-auto px-4 py-4">
            <a href={url} className={className}>
                {label || 'Klik di sini'}
            </a>
        </div>
    );
}

export const buttonFields = {
    label: { type: 'text' as const, label: 'Label' },
    href: { type: 'text' as const, label: 'URL' },
    variant: {
        type: 'select' as const,
        label: 'Variant',
        options: [
            { label: 'Primary', value: 'primary' },
            { label: 'Outline', value: 'outline' },
        ],
    },
};

export const buttonDefaultProps: ButtonProps = {
    label: 'Klik di sini',
    href: '/',
    variant: 'primary',
};
