import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Slider = {
    id: number;
    title: string | null;
    subtitle?: string | null;
    imageUrl: string;
    linkUrl: string | null;
    ctaLabel?: string | null;
};

export function HeroSlider({ sliders }: { sliders: Slider[] }) {
    const [activeSlide, setActiveSlide] = useState(0);
    const [paused, setPaused] = useState(false);

    const goTo = useCallback(
        (index: number) => {
            if (sliders.length === 0) {
                return;
            }
            setActiveSlide((index + sliders.length) % sliders.length);
        },
        [sliders.length],
    );

    const goPrev = () => goTo(activeSlide - 1);
    const goNext = () => goTo(activeSlide + 1);

    useEffect(() => {
        if (sliders.length <= 1 || paused) {
            return;
        }

        const timer = setInterval(() => {
            setActiveSlide((current) => (current + 1) % sliders.length);
        }, 5000);

        return () => clearInterval(timer);
    }, [sliders.length, paused]);

    if (!sliders.length) {
        return null;
    }

    return (
        <section className="pt-4">
            <div className="container mx-auto px-4">
                <div
                    className="group relative min-h-[320px] overflow-hidden rounded-2xl shadow-sm md:min-h-[380px]"
                    onMouseEnter={() => setPaused(true)}
                    onMouseLeave={() => setPaused(false)}
                    onFocusCapture={() => setPaused(true)}
                    onBlurCapture={() => setPaused(false)}
                >
                    {sliders.map((slider, index) => (
                        <div
                            key={slider.id}
                            className={cn(
                                'absolute inset-0 transition-opacity duration-700 ease-in-out',
                                index === activeSlide ? 'z-10 opacity-100' : 'z-0 opacity-0 pointer-events-none',
                            )}
                        >
                            <img
                                src={slider.imageUrl}
                                alt={slider.title ?? 'Promo'}
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/70 via-black/35 to-black/10" />

                            <div className="relative z-10 flex h-full min-h-[320px] flex-col justify-center px-6 py-10 md:min-h-[380px] md:px-10 lg:px-12">
                                <div className="max-w-xl">
                                    {slider.title && (
                                        <h2 className="text-2xl font-bold leading-tight tracking-tight text-white md:text-3xl lg:text-4xl">
                                            {slider.title}
                                        </h2>
                                    )}
                                    {slider.subtitle && (
                                        <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/90 md:text-base">
                                            {slider.subtitle}
                                        </p>
                                    )}
                                    {slider.linkUrl && (
                                        <div className="mt-6">
                                            <Button
                                                asChild
                                                size="lg"
                                                className="rounded-md bg-foreground px-6 text-background hover:bg-foreground/90"
                                            >
                                                <Link href={slider.linkUrl}>
                                                    {slider.ctaLabel?.trim() || 'Jelajahi'}
                                                    <ArrowRight className="ml-2 size-4" />
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}

                    {sliders.length > 1 && (
                        <>
                            <button
                                type="button"
                                onClick={goPrev}
                                className="absolute left-3 top-1/2 z-20 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background/70 text-foreground opacity-0 shadow-md backdrop-blur-sm transition-all duration-200 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto hover:bg-background focus-visible:opacity-100 focus-visible:pointer-events-auto md:left-4"
                                aria-label="Slide sebelumnya"
                            >
                                <ChevronLeft className="size-5" />
                            </button>
                            <button
                                type="button"
                                onClick={goNext}
                                className="absolute right-3 top-1/2 z-20 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-background/70 text-foreground opacity-0 shadow-md backdrop-blur-sm transition-all duration-200 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto hover:bg-background focus-visible:opacity-100 focus-visible:pointer-events-auto md:right-4"
                                aria-label="Slide berikutnya"
                            >
                                <ChevronRight className="size-5" />
                            </button>

                            <div className="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                                {sliders.map((slider, index) => (
                                    <button
                                        key={slider.id}
                                        type="button"
                                        onClick={() => goTo(index)}
                                        className={cn(
                                            'size-2.5 rounded-full transition-all',
                                            index === activeSlide
                                                ? 'scale-110 bg-primary'
                                                : 'bg-white/50 hover:bg-white/70',
                                        )}
                                        aria-label={`Slide ${index + 1}`}
                                        aria-current={index === activeSlide}
                                    />
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </section>
    );
}
