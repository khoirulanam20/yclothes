import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Props = {
    title?: string;
    subtitle?: string;
    ctaLabel?: string;
    ctaHref?: string;
    imageUrl?: string;
    imageAlt?: string;
};

export function PromotionBanner({
    title,
    subtitle,
    ctaLabel,
    ctaHref,
    imageUrl,
    imageAlt,
}: Props) {
    if (!title && !subtitle && !imageUrl) {
        return null;
    }

    return (
        <section className="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-primary/10 via-background to-primary/5">
            <div
                className="pointer-events-none absolute inset-0 opacity-[0.35]"
                style={{
                    backgroundImage: 'radial-gradient(circle, hsl(var(--primary) / 0.15) 1px, transparent 1px)',
                    backgroundSize: '20px 20px',
                }}
                aria-hidden
            />

            <div className="relative grid items-center gap-6 p-6 md:grid-cols-2 md:p-10 lg:p-12">
                <div className="order-2 md:order-1">
                    {title && (
                        <h2 className="text-2xl font-bold tracking-tight text-foreground md:text-3xl lg:text-4xl">
                            {title}
                        </h2>
                    )}
                    {subtitle && (
                        <p className="mt-3 max-w-lg text-sm leading-relaxed text-muted-foreground md:text-base">
                            {subtitle}
                        </p>
                    )}
                    {ctaLabel && ctaHref && (
                        <Button asChild size="lg" className="mt-6 rounded-lg shadow-sm">
                            <Link href={ctaHref}>
                                {ctaLabel}
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                    )}
                </div>

                {imageUrl && (
                    <div className="order-1 md:order-2">
                        <div className="relative mx-auto max-w-sm md:max-w-none">
                            <div className="absolute -right-6 -top-6 size-32 rounded-full bg-primary/10 blur-2xl" aria-hidden />
                            <div className="relative overflow-hidden rounded-2xl border bg-background/60 shadow-lg backdrop-blur-sm">
                                <img
                                    src={imageUrl}
                                    alt={imageAlt || title || 'Promosi'}
                                    className="aspect-[4/3] w-full object-cover md:aspect-[5/4]"
                                />
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
