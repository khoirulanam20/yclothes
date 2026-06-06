import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type Props = {
    images: string[];
    activeImage: string;
    onActiveChange: (url: string) => void;
    overlayLabel?: string | null;
};

export function ProductGallery({ images, activeImage, onActiveChange, overlayLabel }: Props) {
    const scrollRef = useRef<HTMLDivElement>(null);

    const scrollThumbs = (direction: 'left' | 'right') => {
        scrollRef.current?.scrollBy({ left: direction === 'left' ? -200 : 200, behavior: 'smooth' });
    };

    const mainImage = activeImage || images[0] || '';

    return (
        <div className="space-y-3">
            <div className="relative aspect-square overflow-hidden rounded-xl border border-border/60 bg-muted">
                {overlayLabel && (
                    <Badge className="absolute left-3 top-3 z-10 border-transparent bg-foreground/80 text-background">
                        {overlayLabel}
                    </Badge>
                )}
                {mainImage ? (
                    <img src={mainImage} alt="" className="h-full w-full object-cover" />
                ) : (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
                        Tidak ada gambar
                    </div>
                )}
            </div>

            {images.length > 1 && (
                <div className="relative">
                    {images.length > 5 && (
                        <>
                            <button
                                type="button"
                                onClick={() => scrollThumbs('left')}
                                className="absolute left-0 top-1/2 z-10 -translate-y-1/2 rounded-full border bg-background/90 p-1 shadow-sm"
                                aria-label="Thumbnail sebelumnya"
                            >
                                <ChevronLeft className="size-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => scrollThumbs('right')}
                                className="absolute right-0 top-1/2 z-10 -translate-y-1/2 rounded-full border bg-background/90 p-1 shadow-sm"
                                aria-label="Thumbnail berikutnya"
                            >
                                <ChevronRight className="size-4" />
                            </button>
                        </>
                    )}
                    <div ref={scrollRef} className="store-scroll-x flex gap-2 overflow-x-auto px-1 pb-1">
                        {images.map((url) => (
                            <button
                                key={url}
                                type="button"
                                onClick={() => onActiveChange(url)}
                                className={cn(
                                    'h-[72px] w-[72px] shrink-0 overflow-hidden rounded-lg border-2 bg-background transition-colors',
                                    (activeImage || images[0]) === url
                                        ? 'border-primary'
                                        : 'border-border/60 hover:border-primary/40',
                                )}
                            >
                                <img src={url} alt="" className="h-full w-full object-cover" />
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
