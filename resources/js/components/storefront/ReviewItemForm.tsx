import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { ReviewImageUpload } from '@/components/storefront/ReviewImageUpload';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getCsrfToken } from '@/lib/csrf';
import { guestToast } from '@/lib/guestToast';
import { cn } from '@/lib/utils';

type Props = {
    itemId: number;
    productName: string;
    submitUrl: string;
};

export function ReviewItemForm({ itemId, productName, submitUrl }: Props) {
    const [rating, setRating] = useState(5);
    const [review, setReview] = useState('');
    const [images, setImages] = useState<File[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const submit = async () => {
        setSubmitting(true);

        try {
            const formData = new FormData();
            formData.append('order_item_id', String(itemId));
            formData.append('rating', String(rating));
            formData.append('review', review);
            images.forEach((file, index) => {
                formData.append(`images[${index}]`, file);
            });

            const response = await fetch(submitUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                guestToast.error('Gagal mengirim ulasan. Periksa kembali data Anda.');
                return;
            }

            guestToast.success('Ulasan berhasil dikirim.');
            router.reload();
        } catch {
            guestToast.error('Gagal mengirim ulasan.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="rounded-xl border bg-card p-4 space-y-3">
            <p className="font-medium text-sm">{productName}</p>

            <div className="space-y-2">
                <Label className="text-xs">Rating</Label>
                <div className="flex gap-1">
                    {[1, 2, 3, 4, 5].map((value) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => setRating(value)}
                            className="rounded p-0.5 transition-colors hover:bg-muted"
                            aria-label={`${value} bintang`}
                        >
                            <Star
                                className={cn(
                                    'size-5',
                                    value <= rating
                                        ? 'fill-amber-400 text-amber-400'
                                        : 'text-muted-foreground/30',
                                )}
                            />
                        </button>
                    ))}
                </div>
            </div>

            <Textarea
                rows={3}
                placeholder="Ceritakan pengalaman Anda dengan produk ini..."
                value={review}
                onChange={(e) => setReview(e.target.value)}
            />

            <ReviewImageUpload files={images} onChange={setImages} />

            <Button size="sm" onClick={submit} disabled={submitting}>
                {submitting ? 'Mengirim...' : 'Kirim Ulasan'}
            </Button>
        </div>
    );
}
