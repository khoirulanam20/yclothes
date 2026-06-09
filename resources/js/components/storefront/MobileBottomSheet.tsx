import { XIcon } from 'lucide-react';
import { PropsWithChildren } from 'react';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

type Props = PropsWithChildren<{
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    className?: string;
    contentClassName?: string;
}>;

export function MobileBottomSheet({
    open,
    onOpenChange,
    title,
    className,
    contentClassName,
    children,
}: Props) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                showCloseButton={false}
                className={cn(
                    'flex max-h-[min(85dvh,calc(100dvh-5rem))] flex-col gap-0 overflow-hidden rounded-t-2xl border-t p-0',
                    className,
                )}
            >
                <div className="flex shrink-0 flex-col items-center border-b bg-background pt-3">
                    <div className="mb-2 h-1 w-10 rounded-full bg-muted-foreground/25" aria-hidden />
                    <SheetHeader className="flex w-full flex-row items-center justify-between gap-3 px-4 pb-3 pt-0 text-left">
                        <SheetTitle className="text-base font-bold">{title}</SheetTitle>
                        <SheetClose className="flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                            <XIcon className="size-4" />
                            <span className="sr-only">Tutup</span>
                        </SheetClose>
                    </SheetHeader>
                </div>
                <div className={cn('min-h-0 flex-1 overflow-y-auto', contentClassName)}>{children}</div>
            </SheetContent>
        </Sheet>
    );
}
