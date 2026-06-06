import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { HeroSlider } from '@/components/storefront/HeroSlider';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

type Slider = { id: number; title: string | null; imageUrl: string; linkUrl: string | null };
type BlogPost = { id: number; title: string; slug: string; excerpt: string | null; featuredImageUrl: string | null };
type Category = { id: number; name: string; slug: string; imageUrl: string | null };

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
    const isScroll = layout === 'scroll';

    return (
        <SectionCard
            title={title}
            action={actionLabel && actionHref ? { label: actionLabel, href: actionHref } : undefined}
        >
            {isScroll ? (
                <div className="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
                    {products.map((p) => (
                        <div key={p.id} className="w-[160px] shrink-0">
                            <ProductCard product={p} compact />
                        </div>
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    {products.map((p) => (
                        <ProductCard key={p.id} product={p} />
                    ))}
                </div>
            )}
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
                            <div className="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
                                {section.products.map((p) => (
                                    <div key={p.id} className="w-[160px] shrink-0">
                                        <ProductCard product={p} compact />
                                    </div>
                                ))}
                            </div>
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
                    <SectionCard title={(props.title as string) || 'Kategori'}>
                        <div
                            className="grid gap-3"
                            style={{
                                gridTemplateColumns: `repeat(${Math.min(Number(props.columns) || 4, 6)}, minmax(0, 1fr))`,
                            }}
                        >
                            {section.categories.map((cat) => (
                                <Link
                                    key={cat.id}
                                    href={`/products?category=${cat.slug}`}
                                    className="rounded-lg border bg-card p-3 text-center hover:border-primary transition-colors"
                                >
                                    {(props.showImages as boolean) !== false && cat.imageUrl && (
                                        <img src={cat.imageUrl} alt={cat.name} className="mx-auto h-16 w-16 object-cover rounded-full mb-2" />
                                    )}
                                    <span className="text-sm font-medium">{cat.name}</span>
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
                        <section className="relative overflow-hidden rounded-2xl bg-primary/5 border">
                            {props.imageUrl ? (
                                <img
                                    src={String(props.imageUrl)}
                                    alt={(typeof props.imageAlt === 'string' ? props.imageAlt : '') || (typeof props.title === 'string' ? props.title : '') || 'Promosi'}
                                    className="w-full max-h-64 object-cover"
                                />
                            ) : null}
                            <div className="p-6 md:p-8">
                                {typeof props.title === 'string' && props.title && (
                                    <h2 className="text-2xl font-semibold">{props.title}</h2>
                                )}
                                {typeof props.subtitle === 'string' && props.subtitle && (
                                    <p className="text-muted-foreground mt-2">{props.subtitle}</p>
                                )}
                                {typeof props.ctaLabel === 'string' && props.ctaLabel && typeof props.ctaHref === 'string' && props.ctaHref && (
                                    <Button asChild className="mt-4">
                                        <Link href={props.ctaHref}>{props.ctaLabel}</Link>
                                    </Button>
                                )}
                            </div>
                        </section>
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
                        title={(props.title as string) || 'Blog Terbaru'}
                        action={
                            props.actionLabel && props.actionHref
                                ? { label: props.actionLabel as string, href: props.actionHref as string }
                                : undefined
                        }
                    >
                        <div className="grid md:grid-cols-3 gap-4">
                            {section.posts.map((post) => (
                                <Card key={post.id} className="overflow-hidden border shadow-none">
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
