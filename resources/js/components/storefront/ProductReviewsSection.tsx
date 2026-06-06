import { Star } from 'lucide-react';
import { cn } from '@/lib/utils';

export type ProductReview = {
    id: number;
    rating: number;
    comment: string;
    customerName: string;
    createdAt?: string;
    imagesUrl?: string[];
};

type Props = {
    ratingAvg?: number;
    reviewCount?: number;
    reviews: ProductReview[];
};

function RatingSummary({
    ratingAvg,
    reviewCount,
    reviews,
}: {
    ratingAvg?: number;
    reviewCount?: number;
    reviews: ProductReview[];
}) {
    const distribution = [5, 4, 3, 2, 1].map((star) => ({
        star,
        count: reviews.filter((r) => r.rating === star).length,
    }));
    const maxCount = Math.max(...distribution.map((d) => d.count), 1);
    const satisfiedPercent =
        reviewCount && reviewCount > 0
            ? Math.round((reviews.filter((r) => r.rating >= 4).length / reviewCount) * 100)
            : 0;

    return (
        <div className="mb-6 flex flex-wrap gap-6 rounded-xl border bg-muted/20 p-4">
            <div className="min-w-[120px] text-center">
                <p className="text-3xl font-bold">
                    {ratingAvg?.toFixed(1) ?? '—'}
                    <span className="text-lg font-normal text-muted-foreground"> / 5.0</span>
                </p>
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
                {satisfiedPercent > 0 && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {satisfiedPercent}% pembeli merasa puas
                    </p>
                )}
                <p className="mt-1 text-xs text-muted-foreground">{reviewCount ?? 0} ulasan</p>
            </div>
            <div className="min-w-[200px] flex-1 space-y-1.5">
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

export function ProductReviewsSection({ ratingAvg, reviewCount, reviews }: Props) {
    const buyerPhotos = reviews.flatMap((r) => r.imagesUrl ?? []);

    return (
        <section>
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-foreground">
                Ulasan Pembeli
            </h2>

            {(ratingAvg ?? 0) > 0 && (
                <RatingSummary ratingAvg={ratingAvg} reviewCount={reviewCount} reviews={reviews} />
            )}

            {buyerPhotos.length > 0 && (
                <div className="mb-6">
                    <p className="mb-2 text-sm font-medium">Foto Pembeli</p>
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
                <div className="rounded-xl border border-dashed bg-muted/10 px-4 py-10 text-center">
                    <p className="text-sm font-medium text-foreground">Belum ada ulasan untuk produk ini</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Jadilah yang pertama memberikan ulasan setelah pembelian.
                    </p>
                </div>
            )}
        </section>
    );
}
