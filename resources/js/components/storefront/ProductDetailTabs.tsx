import { Link } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';
import { normalizeCmsHtml } from '@/cms/normalizeCmsHtml';
import { cn } from '@/lib/utils';

type Category = {
    name: string;
    slug: string;
};

type SpecRow = {
    label: string;
    value: ReactNode;
};

type Props = {
    description?: string | null;
    shortDescription?: string | null;
    category?: Category | null;
    weightLabel?: string | null;
    minPurchaseQty?: number;
};

function SpecList({ rows }: { rows: SpecRow[] }) {
    return (
        <dl className="space-y-3 border-b pb-5 text-sm">
            {rows.map((row) => (
                <div key={row.label} className="grid grid-cols-[7.5rem_1fr] gap-x-4 gap-y-1">
                    <dt className="text-muted-foreground">{row.label}</dt>
                    <dd className="font-semibold text-foreground">{row.value}</dd>
                </div>
            ))}
        </dl>
    );
}

export function ProductDetailTabs({
    description,
    shortDescription,
    category,
    weightLabel,
    minPurchaseQty = 1,
}: Props) {
    const [expanded, setExpanded] = useState(false);

    const plainDescription = description?.replace(/<[^>]+>/g, '') ?? '';
    const isLong = plainDescription.length > 280;

    const specRows: SpecRow[] = [
        { label: 'Kondisi', value: 'Baru' },
        ...(weightLabel ? [{ label: 'Berat Satuan', value: weightLabel }] : []),
        { label: 'Min. Beli', value: `${minPurchaseQty} Buah` },
        ...(category
            ? [
                  {
                      label: 'Kategori',
                      value: (
                          <Link
                              href={`/products?category=${category.slug}`}
                              className="font-semibold text-primary hover:underline"
                          >
                              {category.name}
                          </Link>
                      ),
                  },
              ]
            : []),
        {
            label: 'Etalase',
            value: (
                <Link href="/products" className="font-semibold text-primary hover:underline">
                    Semua Produk
                </Link>
            ),
        },
    ];

    return (
        <div className="mt-4">
            <div className="border-b">
                <p className="inline-block border-b-2 border-primary pb-3 pt-1 text-sm font-semibold text-primary">
                    Detail Produk
                </p>
            </div>

            <div className="space-y-5 pt-5">
                <SpecList rows={specRows} />

                {shortDescription && (
                    <p className="text-sm font-medium leading-relaxed text-foreground">{shortDescription}</p>
                )}

                {description ? (
                    <div>
                        <div className="relative">
                            <div
                                className={cn(
                                    'cms-content max-w-none',
                                    !expanded && isLong && 'max-h-48 overflow-hidden',
                                )}
                                dangerouslySetInnerHTML={{ __html: normalizeCmsHtml(description) }}
                            />
                            {!expanded && isLong && (
                                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-card to-transparent" />
                            )}
                        </div>
                        {isLong && (
                            <button
                                type="button"
                                onClick={() => setExpanded((v) => !v)}
                                className="mt-3 text-sm font-semibold text-primary hover:underline"
                            >
                                {expanded ? 'Sembunyikan' : 'Lihat selengkapnya'}
                            </button>
                        )}
                    </div>
                ) : !shortDescription ? (
                    <p className="text-sm text-muted-foreground">Belum ada deskripsi produk.</p>
                ) : null}
            </div>
        </div>
    );
}
