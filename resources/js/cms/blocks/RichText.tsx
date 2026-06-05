export type RichTextProps = {
    html: string;
};

export function RichTextBlock({ html }: RichTextProps) {
    if (!html) {
        return (
            <div className="container mx-auto px-4 py-4">
                <div className="rounded-lg border border-dashed bg-muted/50 px-4 py-8 text-center text-sm text-muted-foreground">
                    Tambahkan konten HTML
                </div>
            </div>
        );
    }

    return (
        <div
            className="prose prose-neutral max-w-none container mx-auto px-4 py-4"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

export const richTextFields = {
    html: { type: 'textarea' as const, label: 'Konten HTML' },
};

export const richTextDefaultProps: RichTextProps = {
    html: '<p>Konten halaman...</p>',
};
