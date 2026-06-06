import { Star } from 'lucide-react';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import { StorefrontTabs } from '@/components/storefront/StorefrontTabs';
import { type ProductCardData } from '@/components/ProductCard';
import { cn } from '@/lib/utils';
import { useState } from 'react';

type Review = {
    id: number;
    rating: number;
    comment: string;
    customerName: string;
    createdAt?: string;
    imagesUrl?: string[];
};

type Props = {
    description?: string | null;
    ratingAvg?: number;
    reviewCount?: number;
    reviews: Review[];
    relatedProducts: ProductCardData[];
    upSellProducts: ProductCardData[];
};

function RatingSummary({ ratingAvg, reviewCount, reviews }: { ratingAvg?: number; reviewCount?: number; reviews: Review[] }) {
    const distribution = [5, 4, 3, 2, 1].map((star) => ({
        star,
        count: reviews.filter((r) => r.rating === star).length,
    }));
    const maxCount = Math.max(...distribution.map((d) => d.count), 1);

    return (
        <div className="mb-6 flex flex-wrap gap-6">
            <div className="text-center">
                <p className="text-4xl font-bold">{ratingAvg?.toFixed(1) ?? '—'}</p>
                <div className="mt-1 flex items-center justify-center gap-0.5">
                    {[1, 2, 3, 4, 5].map((i) => (
                        <Star
                            key={i}
                            className={cn(
                                'size-4',
                                i <= Math.round(ratingAvg ?? 0)
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-muted-foreground/30',
                            )}
                        />
                    ))}
                </div>
                <p className="mt-1 text-xs text-muted-foreground">{reviewCount ?? 0} ulasan</p>
            </div>
            <div className="flex-1 min-w-[200px] space-y-1.5">
                {distribution.map(({ star, count }) => (
                    <div key={star} className="flex items-center gap-2 text-xs">
                        <span className="w-3">{star}</span>
                        <Star className="size-3 fill-amber-400 text-amber-400" />
                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full bg-primary transition-all"
                                style={{ width: `${(count / maxCount) * 100}%` }}
                            />
                        </div>
                        <span className="w-6 text-muted-foreground">{count}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function ProductDetailTabs({
    description,
    ratingAvg,
    reviewCount,
    reviews,
    relatedProducts,
    upSellProducts,
}: Props) {
    const [activeTab, setActiveTab] = useState('detail');
    const [expanded, setExpanded] = useState(false);

    const buyerPhotos = reviews.flatMap((r) => r.imagesUrl ?? []);
    const recommendationProducts = [...upSellProducts, ...relatedProducts].filter(
        (product, index, arr) => arr.findIndex((p) => p.id === product.id) === index,
    );

    const tabs = [
        { id: 'detail', label: 'Detail Produk' },
        { id: 'reviews', label: `Ulasan${reviewCount ? ` (${reviewCount})` : ''}` },
        { id: 'recommendations', label: 'Rekomendasi' },
    ];

    const plainDescription = description?.replace(/<[^>]+>/g, '') ?? '';
    const isLong = plainDescription.length > 280;

    return (
        <div className="space-y-4">
            <StorefrontTabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

            {activeTab === 'detail' && (
                <div className="py-4 space-y-4">
                    {description ? (
                        <div>
                            <div
                                className={cn(
                                    'prose prose-sm max-w-none text-muted-foreground',
                                    !expanded && isLong && 'line-clamp-6',
                                )}
                                dangerouslySetInnerHTML={{ __html: description }}
                            />
                            {isLong && (
                                <button
                                    type="button"
                                    onClick={() => setExpanded((v) => !v)}
                                    className="mt-2 text-sm font-medium text-primary hover:underline"
                                >
                                    {expanded ? 'Sembunyikan' : 'Lihat selengkapnya'}
                                </button>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum ada deskripsi produk.</p>
                    )}
                </div>
            )}

            {activeTab === 'reviews' && (
                <div className="py-4">
                    {(ratingAvg ?? 0) > 0 && (
                        <RatingSummary ratingAvg={ratingAvg} reviewCount={reviewCount} reviews={reviews} />
                    )}

                    {buyerPhotos.length > 0 && (
                        <div className="mb-6">
                            <p className="mb-2 text-sm font-medium">Foto & Video Pembeli</p>
                            <div className="store-scroll-x flex gap-2 overflow-x-auto pb-1">
                                {buyerPhotos.map((url, index) => (
                                    <img
                                        key={`${url}-${index}`}
                                        src={url}
                                        alt=""
                                        className="size-20 shrink-0 rounded-lg border object-cover"
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {reviews.length > 0 ? (
                        <div className="space-y-3">
                            {reviews.map((review) => (
                                <div key={review.id} className="rounded-xl border bg-card p-4">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-sm font-medium">{review.customerName}</span>
                                        <div className="flex items-center gap-0.5">
                                            {Array.from({ length: review.rating }).map((_, i) => (
                                                <Star key={i} className="size-3.5 fill-amber-400 text-amber-400" />
                                            ))}
                                        </div>
                                    </div>
                                    {review.createdAt && (
                                        <p className="mt-0.5 text-xs text-muted-foreground">{review.createdAt}</p>
                                    )}
                                    <p className="mt-2 text-sm text-muted-foreground">{review.comment}</p>
                                    {review.imagesUrl && review.imagesUrl.length > 0 && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {review.imagesUrl.map((url, index) => (
                                                <img
                                                    key={`${url}-${index}`}
                                                    src={url}
                                                    alt=""
                                                    className="size-16 rounded-md border object-cover"
                                                />
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum ada ulasan untuk produk ini.</p>
                    )}
                </div>
            )}

            {activeTab === 'recommendations' && (
                <div className="py-4">
                    {recommendationProducts.length > 0 ? (
                        <ProductGrid products={recommendationProducts} columns="wide" />
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum ada rekomendasi produk.</p>
                    )}
                </div>
            )}
        </div>
    );
}
