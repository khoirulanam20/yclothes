import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    images: string[];
    activeIndex: number;
    onIndexChange: (index: number) => void;
    title?: string;
};

export function ReviewImageModal({
    open,
    onOpenChange,
    images,
    activeIndex,
    onIndexChange,
    title = 'Foto Ulasan',
}: Props) {
    const current = images[activeIndex];
    const hasPrev = activeIndex > 0;
    const hasNext = activeIndex < images.length - 1;

    const goPrev = () => {
        if (hasPrev) {
            onIndexChange(activeIndex - 1);
        }
    };

    const goNext = () => {
        if (hasNext) {
            onIndexChange(activeIndex + 1);
        }
    };

    if (!current) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl gap-0 overflow-hidden p-0">
                <DialogTitle className="sr-only">{title}</DialogTitle>
                <div className="relative flex min-h-[280px] items-center justify-center bg-black/95">
                    <img
                        src={current}
                        alt=""
                        className="max-h-[75vh] max-w-full object-contain"
                    />

                    {images.length > 1 && (
                        <>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="absolute left-2 top-1/2 -translate-y-1/2 text-white hover:bg-white/20 hover:text-white disabled:opacity-30"
                                disabled={!hasPrev}
                                onClick={goPrev}
                            >
                                <ChevronLeft className="size-6" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="absolute right-2 top-1/2 -translate-y-1/2 text-white hover:bg-white/20 hover:text-white disabled:opacity-30"
                                disabled={!hasNext}
                                onClick={goNext}
                            >
                                <ChevronRight className="size-6" />
                            </Button>
                            <div className="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-xs text-white">
                                {activeIndex + 1} / {images.length}
                            </div>
                        </>
                    )}

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute right-2 top-2 text-white hover:bg-white/20 hover:text-white"
                        onClick={() => onOpenChange(false)}
                    >
                        <X className="size-5" />
                    </Button>
                </div>

                {images.length > 1 && (
                    <div className="flex gap-2 overflow-x-auto border-t bg-background p-3">
                        {images.map((url, index) => (
                            <button
                                key={`${url}-${index}`}
                                type="button"
                                onClick={() => onIndexChange(index)}
                                className={`shrink-0 overflow-hidden rounded-md border-2 transition-colors ${
                                    index === activeIndex ? 'border-primary' : 'border-transparent opacity-70 hover:opacity-100'
                                }`}
                            >
                                <img src={url} alt="" className="size-14 object-cover" />
                            </button>
                        ))}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
