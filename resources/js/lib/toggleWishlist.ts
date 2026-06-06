import { getCsrfToken } from '@/lib/csrf';

export type ToggleWishlistResult = {
    success: boolean;
    in_wishlist: boolean;
};

export async function toggleWishlist(productId: number): Promise<ToggleWishlistResult> {
    const response = await fetch('/account/wishlist/toggle', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ product_id: productId }),
    });

    if (response.status === 401) {
        window.location.href = '/account/login';
        throw new Error('Silakan login terlebih dahulu.');
    }

    const data = (await response.json()) as ToggleWishlistResult & { message?: string };

    if (!response.ok) {
        throw new Error(data.message ?? 'Gagal memperbarui wishlist.');
    }

    return data;
}
