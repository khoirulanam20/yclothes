export type TwoColumnsProps = {
    leftHtml: string;
    rightHtml: string;
};

export function TwoColumnsBlock({ leftHtml, rightHtml }: TwoColumnsProps) {
    return (
        <div className="container mx-auto px-4 py-4 grid md:grid-cols-2 gap-8">
            <div className="prose prose-neutral max-w-none" dangerouslySetInnerHTML={{ __html: leftHtml }} />
            <div className="prose prose-neutral max-w-none" dangerouslySetInnerHTML={{ __html: rightHtml }} />
        </div>
    );
}

export const twoColumnsFields = {
    leftHtml: { type: 'textarea' as const, label: 'Kolom Kiri (HTML)' },
    rightHtml: { type: 'textarea' as const, label: 'Kolom Kanan (HTML)' },
};

export const twoColumnsDefaultProps: TwoColumnsProps = {
    leftHtml: '<p>Kolom kiri</p>',
    rightHtml: '<p>Kolom kanan</p>',
};
