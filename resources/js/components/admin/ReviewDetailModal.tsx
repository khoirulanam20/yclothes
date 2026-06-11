import { Link, router } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { useState } from 'react';
import { AdminApproveAction, AdminRejectAction } from '@/components/admin/AdminTableActions';
import { ReviewImageModal } from '@/components/storefront/ReviewImageModal';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';

export type AdminReview = {
    id: number;
    rating: number;
    comment: string;
    customerName: string;
    createdAt?: string;
    product?: { id: number; name: string } | null;
    isApproved?: boolean;
    imagesUrl?: string[];
};

type Props = {
    review: AdminReview | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    status: string;
    onReject?: (review: AdminReview) => void;
};

function formatDate(iso?: string): string | null {
    if (!iso) {
        return null;
    }

    try {
        return new Date(iso).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return null;
    }
}

export function ReviewDetailModal({ review, open, onOpenChange, status, onReject }: Props) {
    const confirm = useAdminConfirm();
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);

    if (!review) {
        return null;
    }

    const images = review.imagesUrl ?? [];
    const dateLabel = formatDate(review.createdAt);
    const isPending = status === 'pending';

    const handleReject = async () => {
        const ok = await confirm({
            title: 'Tolak ulasan?',
            description: `Ulasan dari ${review.customerName} akan dihapus.`,
            confirmLabel: 'Tolak',
            cancelLabel: 'Batal',
            variant: 'destructive',
        });

        if (ok) {
            onReject?.(review);
            onOpenChange(false);
        }
    };

    const openImage = (index: number) => {
        setActiveIndex(index);
        setLightboxOpen(true);
    };

    return (
        <>
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent className="max-h-[90vh] max-w-lg overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Detail Ulasan</DialogTitle>
                        <DialogDescription>
                            {review.product?.name ?? 'Produk tidak ditemukan'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 text-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={isPending ? 'secondary' : 'default'}>
                                {isPending ? 'Menunggu persetujuan' : 'Disetujui'}
                            </Badge>
                            <div className="flex items-center gap-0.5">
                                {Array.from({ length: review.rating }).map((_, i) => (
                                    <Star key={i} className="size-4 fill-amber-400 text-amber-400" />
                                ))}
                                <span className="ml-1 text-muted-foreground">{review.rating}/5</span>
                            </div>
                        </div>

                        <div className="grid gap-2 rounded-lg border bg-muted/20 p-3">
                            <div>
                                <p className="text-xs text-muted-foreground">Customer</p>
                                <p className="font-medium">{review.customerName}</p>
                            </div>
                            {review.product && (
                                <div>
                                    <p className="text-xs text-muted-foreground">Produk</p>
                                    <Link
                                        href={`/admin/products/${review.product.id}/edit`}
                                        className="font-medium text-primary hover:underline"
                                    >
                                        {review.product.name}
                                    </Link>
                                </div>
                            )}
                            {dateLabel && (
                                <div>
                                    <p className="text-xs text-muted-foreground">Tanggal</p>
                                    <p>{dateLabel}</p>
                                </div>
                            )}
                        </div>

                        <div>
                            <p className="mb-1 text-xs font-medium text-muted-foreground">Komentar</p>
                            <p className="leading-relaxed whitespace-pre-wrap">
                                {review.comment || '—'}
                            </p>
                        </div>

                        {images.length > 0 && (
                            <div>
                                <p className="mb-2 text-xs font-medium text-muted-foreground">
                                    Foto ({images.length})
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {images.map((url, index) => (
                                        <button
                                            key={`${url}-${index}`}
                                            type="button"
                                            onClick={() => openImage(index)}
                                            className="overflow-hidden rounded-lg border transition-opacity hover:opacity-90"
                                        >
                                            <img src={url} alt="" className="size-20 object-cover" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {isPending && (
                            <div className="flex justify-end gap-1 border-t pt-4">
                                <AdminApproveAction
                                    onClick={() => {
                                        router.post(`/admin/reviews/${review.id}/approve`, {}, {
                                            preserveScroll: true,
                                            onSuccess: () => onOpenChange(false),
                                        });
                                    }}
                                />
                                <AdminRejectAction onClick={() => void handleReject()} />
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            <ReviewImageModal
                open={lightboxOpen}
                onOpenChange={setLightboxOpen}
                images={images}
                activeIndex={activeIndex}
                onIndexChange={setActiveIndex}
            />
        </>
    );
}
