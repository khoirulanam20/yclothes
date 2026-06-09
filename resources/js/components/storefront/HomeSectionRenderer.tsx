import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import type { ProductCardData } from '@/components/ProductCard';
import { HeroSlider } from '@/components/storefront/HeroSlider';
import { PromotionBanner } from '@/components/storefront/PromotionBanner';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Slider = { id: number; title: string | null; imageUrl: string; linkUrl: string | null };
type BlogPost = { id: number; title: string; slug: string; excerpt: string | null; featuredImageUrl: string | null };
type Category = { id: number; name: string; slug: string; imageUrl: string | null };

/** Radius seragam dengan hero slider di halaman utama */
const HOME_SECTION_RADIUS = 'rounded-2xl';

export type HomeSection = {
    id: string;
    type: string;
    props: Record<string, unknown>;
    sliders?: Slider[];
    products?: ProductCardData[];
    flashSaleEndsAt?: number;
    categories?: Category[];
    category?: Category;
    posts?: BlogPost[];
};

function FlashCountdown({ endsAt }: { endsAt: number }) {
    const [remaining, setRemaining] = useState(() => Math.max(0, endsAt - Math.floor(Date.now() / 1000)));

    useEffect(() => {
        const tick = () => setRemaining(Math.max(0, endsAt - Math.floor(Date.now() / 1000)));
        tick();
        const id = window.setInterval(tick, 1000);
        return () => window.clearInterval(id);
    }, [endsAt]);

    const h = Math.floor(remaining / 3600);
    const m = Math.floor((remaining % 3600) / 60);
    const s = remaining % 60;
    return (
        <span className="font-mono text-xs bg-primary/10 text-primary px-2 py-1 rounded">
            {String(h).padStart(2, '0')}:{String(m).padStart(2, '0')}:{String(s).padStart(2, '0')}
        </span>
    );
}

function ProductGridSection({
    title,
    products,
    layout,
    actionLabel,
    actionHref,
}: {
    title: string;
    products: ProductCardData[];
    layout?: string;
    actionLabel?: string;
    actionHref?: string;
}) {
    return (
        <SectionCard
            className={cn(HOME_SECTION_RADIUS, layout === 'scroll' && 'group')}
            title={title}
            action={actionLabel && actionHref ? { label: actionLabel, href: actionHref } : undefined}
        >
            <ProductGrid
                products={products}
                layout={layout === 'scroll' ? 'scroll' : 'grid'}
                compact={layout === 'scroll'}
                hoverGroupParent={layout === 'scroll'}
            />
        </SectionCard>
    );
}

function SectionHeadMeta({ props }: { props: Record<string, unknown> }) {
    const metaTitle = props.metaTitle as string | null | undefined;
    const metaDescription = props.metaDescription as string | null | undefined;
    if (!metaTitle && !metaDescription) {
        return null;
    }

    return (
        <Head>
            {metaTitle && <title>{metaTitle}</title>}
            {metaDescription && <meta name="description" content={metaDescription} />}
        </Head>
    );
}

export function HomeSectionRenderer({ section }: { section: HomeSection }) {
    const props = section.props ?? {};

    switch (section.type) {
        case 'hero_slider':
            return section.sliders?.length ? <HeroSlider sliders={section.sliders} /> : null;

        case 'flash_sale':
            if (!section.products?.length) {
                return null;
            }
            return (
                <>
                    <SectionHeadMeta props={props} />
                    <PageContainer>
                        <SectionCard
                            className={cn(HOME_SECTION_RADIUS, 'group')}
                            title={(props.title as string) || 'Flash Sale'}
                            variant="primary"
                            headerExtra={
                                (props.showCountdown as boolean) !== false && section.flashSaleEndsAt ? (
                                    <FlashCountdown endsAt={section.flashSaleEndsAt} />
                                ) : undefined
                            }
                            action={
                                props.actionLabel && props.actionHref
                                    ? { label: props.actionLabel as string, href: props.actionHref as string }
                                    : undefined
                            }
                        >
                            <ProductGrid products={section.products} layout="scroll" compact hoverGroupParent />
                        </SectionCard>
                    </PageContainer>
                </>
            );

        case 'category_grid':
            if (!section.categories?.length) {
                return null;
            }
            return (
                <PageContainer>
                    <SectionCard className={HOME_SECTION_RADIUS} title={(props.title as string) || 'Kategori'}>
                        <div
                            className="store-scroll-x -mx-1 flex gap-3 overflow-x-auto pb-1 md:mx-0 md:grid md:gap-3 md:overflow-visible md:pb-0"
                            style={{
                                gridTemplateColumns: `repeat(${Math.max(Number(props.gridColumns) || section.categories.length, 1)}, minmax(0, 1fr))`,
                            }}
                        >
                            {section.categories.map((cat) => (
                                <Link
                                    key={cat.id}
                                    href={`/products?category=${cat.slug}`}
                                    className={cn(
                                        'group store-card store-card-hover shrink-0 border bg-card transition-colors hover:border-primary/40',
                                        'flex size-[4.5rem] items-center justify-center p-2 md:size-auto md:flex-col md:p-3 md:text-center',
                                        HOME_SECTION_RADIUS,
                                    )}
                                    title={cat.name}
                                    aria-label={cat.name}
                                >
                                    {(props.showImages as boolean) !== false && cat.imageUrl ? (
                                        <img
                                            src={cat.imageUrl}
                                            alt={cat.name}
                                            className="size-12 rounded-full object-cover transition-transform group-hover:scale-105 md:mx-auto md:mb-2 md:size-16"
                                        />
                                    ) : (
                                        <span className="flex size-12 items-center justify-center rounded-full bg-muted text-sm font-semibold text-muted-foreground md:hidden">
                                            {cat.name.charAt(0)}
                                        </span>
                                    )}
                                    <span className="hidden text-sm font-medium md:block">{cat.name}</span>
                                </Link>
                            ))}
                        </div>
                    </SectionCard>
                </PageContainer>
            );

        case 'product_grid':
        case 'products_by_category':
            if (!section.products?.length) {
                return null;
            }
            return (
                <PageContainer>
                    <ProductGridSection
                        title={(props.title as string) || 'Produk'}
                        products={section.products}
                        layout={props.layout as string | undefined}
                        actionLabel={props.actionLabel as string | undefined}
                        actionHref={props.actionHref as string | undefined}
                    />
                </PageContainer>
            );

        case 'promotion_banner':
            return (
                <>
                    <SectionHeadMeta props={props} />
                    <PageContainer>
                        <PromotionBanner
                            title={typeof props.title === 'string' ? props.title : undefined}
                            subtitle={typeof props.subtitle === 'string' ? props.subtitle : undefined}
                            ctaLabel={typeof props.ctaLabel === 'string' ? props.ctaLabel : undefined}
                            ctaHref={typeof props.ctaHref === 'string' ? props.ctaHref : undefined}
                            imageUrl={typeof props.imageUrl === 'string' ? props.imageUrl : undefined}
                            imageAlt={typeof props.imageAlt === 'string' ? props.imageAlt : undefined}
                        />
                    </PageContainer>
                </>
            );

        case 'blog_posts':
            if (!section.posts?.length) {
                return null;
            }
            return (
                <PageContainer>
                    <SectionCard
                        className={HOME_SECTION_RADIUS}
                        title={(props.title as string) || 'Blog Terbaru'}
                        action={
                            props.actionLabel && props.actionHref
                                ? { label: props.actionLabel as string, href: props.actionHref as string }
                                : undefined
                        }
                    >
                        <div className="grid md:grid-cols-3 gap-4">
                            {section.posts.map((post) => (
                                <Card key={post.id} className={cn('overflow-hidden border shadow-none', HOME_SECTION_RADIUS)}>
                                    <Link href={`/blog/${post.slug}`}>
                                        {post.featuredImageUrl && (
                                            <img
                                                src={post.featuredImageUrl}
                                                alt=""
                                                className="w-full aspect-video object-cover"
                                            />
                                        )}
                                        <CardContent className="p-3">
                                            <h3 className="font-medium text-sm line-clamp-2">{post.title}</h3>
                                            {post.excerpt && (
                                                <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                                                    {post.excerpt}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Link>
                                </Card>
                            ))}
                        </div>
                    </SectionCard>
                </PageContainer>
            );

        case 'spacer':
            return <div style={{ height: Number(props.height) || 32 }} aria-hidden />;

        default:
            return null;
    }
}
