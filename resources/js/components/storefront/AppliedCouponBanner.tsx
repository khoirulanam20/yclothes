import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    couponCode: string;
    onRemove: () => void;
    className?: string;
};

export function AppliedCouponBanner({ couponCode, onRemove, className }: Props) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800',
                className,
            )}
        >
            <p className="min-w-0 truncate font-semibold">{couponCode}</p>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-8 shrink-0 px-2 text-green-800 hover:bg-green-100 hover:text-green-900"
                onClick={onRemove}
            >
                Hapus
            </Button>
        </div>
    );
}
