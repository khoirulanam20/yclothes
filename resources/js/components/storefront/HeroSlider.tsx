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

    const goTo = useCallback((index: number) => {
        if (sliders.length === 0) {
            return;
        }
        setActiveSlide((index + sliders.length) % sliders.length);
    }, [sliders.length]);

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

    const activeSlider = sliders[activeSlide];
    const ctaLabel = activeSlider.ctaLabel?.trim() || 'Jelajahi';

    return (
        <section className="pt-4">
            <div className="container mx-auto px-4">
                <div
                    className="relative overflow-hidden rounded-2xl bg-primary/5 shadow-sm"
                    onMouseEnter={() => setPaused(true)}
                    onMouseLeave={() => setPaused(false)}
                    onFocusCapture={() => setPaused(true)}
                    onBlurCapture={() => setPaused(false)}
                >
                    <div className="relative min-h-[320px] md:min-h-[380px]">
                        {sliders.map((slider, index) => (
                            <div
                                key={slider.id}
                                className={cn(
                                    'absolute inset-0 transition-all duration-700 ease-in-out',
                                    index === activeSlide
                                        ? 'opacity-100 translate-x-0 z-10'
                                        : index < activeSlide
                                            ? 'opacity-0 -translate-x-8 z-0 pointer-events-none'
                                            : 'opacity-0 translate-x-8 z-0 pointer-events-none',
                                )}
                            >
                                <div className="grid h-full md:grid-cols-2">
                                    <div className="flex flex-col justify-center px-6 py-10 md:px-10 md:py-12 lg:px-14">
                                        {slider.title && (
                                            <h2 className="text-3xl font-bold leading-tight tracking-tight text-foreground md:text-4xl lg:text-5xl">
                                                {slider.title}
                                            </h2>
                                        )}
                                        {slider.subtitle && (
                                            <p className="mt-4 max-w-md text-base leading-relaxed text-muted-foreground md:text-lg">
                                                {slider.subtitle}
                                            </p>
                                        )}
                                        {slider.linkUrl && (
                                            <div className="mt-8">
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
                                    <div className="relative hidden min-h-[280px] md:block">
                                        <div className="absolute inset-y-4 right-4 left-8 overflow-hidden rounded-2xl">
                                            <img
                                                src={slider.imageUrl}
                                                alt={slider.title ?? 'Promo'}
                                                className="h-full w-full object-cover object-center"
                                            />
                                            <div className="pointer-events-none absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-transparent" />
                                        </div>
                                    </div>
                                </div>
                                <img
                                    src={slider.imageUrl}
                                    alt=""
                                    aria-hidden
                                    className="absolute inset-x-0 bottom-0 h-48 object-cover opacity-20 md:hidden"
                                />
                            </div>
                        ))}
                    </div>

                    {sliders.length > 1 && (
                        <>
                            <button
                                type="button"
                                onClick={goPrev}
                                className="absolute left-3 top-1/2 z-20 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-background/70 text-foreground shadow-md backdrop-blur-sm transition hover:bg-background"
                                aria-label="Slide sebelumnya"
                            >
                                <ChevronLeft className="size-5" />
                            </button>
                            <button
                                type="button"
                                onClick={goNext}
                                className="absolute right-3 top-1/2 z-20 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-background/70 text-foreground shadow-md backdrop-blur-sm transition hover:bg-background"
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
                                                : 'bg-muted-foreground/30 hover:bg-muted-foreground/50',
                                        )}
                                        aria-label={`Slide ${index + 1}`}
                                        aria-current={index === activeSlide}
                                    />
                                ))}
                            </div>
                        </>
                    )}

                    {activeSlider.linkUrl && sliders.length === 1 && (
                        <div className="absolute bottom-6 left-6 z-20 md:hidden">
                            <Button asChild size="sm" className="bg-foreground text-background">
                                <Link href={activeSlider.linkUrl}>
                                    {ctaLabel}
                                    <ArrowRight className="ml-1 size-3.5" />
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
