import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Slider = { id: number; title: string | null; imageUrl: string; linkUrl: string | null };

export function HeroSlider({ sliders }: { sliders: Slider[] }) {
    const [activeSlide, setActiveSlide] = useState(0);

    useEffect(() => {
        if (sliders.length <= 1) {
            return;
        }

        const timer = setInterval(() => {
            setActiveSlide((current) => (current + 1) % sliders.length);
        }, 5000);

        return () => clearInterval(timer);
    }, [sliders.length]);

    if (!sliders.length) {
        return null;
    }

    const activeSlider = sliders[activeSlide];

    return (
        <section className="pt-4">
            <div className="container mx-auto px-4">
                <div className="relative overflow-hidden rounded-2xl bg-card shadow-sm aspect-[16/5] sm:aspect-[16/4.5] md:aspect-[16/4]">
                    {sliders.map((slider, index) => (
                        <div
                            key={slider.id}
                            className={cn(
                                'absolute inset-0 transition-opacity duration-700 ease-in-out',
                                index === activeSlide ? 'opacity-100 z-10' : 'opacity-0 z-0',
                            )}
                        >
                            <img
                                src={slider.imageUrl}
                                alt={slider.title ?? 'Promo'}
                                className="h-full w-full object-cover"
                            />
                        </div>
                    ))}

                    {activeSlider?.linkUrl && (
                        <div className="absolute bottom-4 left-4 z-20">
                            <Button asChild size="sm" className="shadow-md">
                                <Link href={activeSlider.linkUrl}>Shop Now</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
