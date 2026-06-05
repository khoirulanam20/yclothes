export type HeadingProps = {
    text: string;
    level: 'h1' | 'h2' | 'h3';
    align: 'left' | 'center' | 'right';
};

export function HeadingBlock({ text, level, align }: HeadingProps) {
    const Tag = level;
    const alignClass =
        align === 'center' ? 'text-center' : align === 'right' ? 'text-right' : 'text-left';
    const sizeClass =
        level === 'h1' ? 'text-3xl md:text-4xl' : level === 'h2' ? 'text-2xl md:text-3xl' : 'text-xl';

    return (
        <div className={`container mx-auto px-4 py-4 ${alignClass}`}>
            <Tag className={`font-serif font-bold ${sizeClass}`}>{text || 'Heading'}</Tag>
        </div>
    );
}

export const headingFields = {
    text: { type: 'text' as const, label: 'Teks' },
    level: {
        type: 'select' as const,
        label: 'Level',
        options: [
            { label: 'H1', value: 'h1' },
            { label: 'H2', value: 'h2' },
            { label: 'H3', value: 'h3' },
        ],
    },
    align: {
        type: 'select' as const,
        label: 'Align',
        options: [
            { label: 'Kiri', value: 'left' },
            { label: 'Tengah', value: 'center' },
            { label: 'Kanan', value: 'right' },
        ],
    },
};

export const headingDefaultProps: HeadingProps = {
    text: 'Heading',
    level: 'h2',
    align: 'left',
};
