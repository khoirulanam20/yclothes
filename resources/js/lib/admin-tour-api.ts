import { getCsrfToken } from '@/lib/csrf';
import type { AdminTourKey } from '@/lib/admin-tour-keys';
import type { TourPageVariant } from '@/lib/admin-tour-routes';

export type CompletedTourVariants = Record<string, TourPageVariant[]>;

export async function markAdminTourComplete(
    tourKey: AdminTourKey,
    variant: TourPageVariant,
): Promise<CompletedTourVariants> {
    const response = await fetch(`/admin/tours/${tourKey}/complete`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ variant }),
    });

    if (!response.ok) {
        throw new Error('Gagal menyimpan status tour.');
    }

    const data = (await response.json()) as { completedTourVariants: CompletedTourVariants };
    return data.completedTourVariants;
}
