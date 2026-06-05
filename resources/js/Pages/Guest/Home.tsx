import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { HeroSlider } from '@/components/storefront/HeroSlider';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Card, CardContent } from '@/components/ui/card';

type Slider = { id: number; title: string | null; imageUrl: string; linkUrl: string | null };
type BlogPost = { id: number; title: string; slug: string; excerpt: string | null; featuredImageUrl: string | null };

type Props = {
    sliders: Slider[];
    featuredProducts: ProductCardData[];
    newProducts: ProductCardData[];
    flashSaleProducts: ProductCardData[];
    flashSaleEndsAt: number;
    latestPosts: BlogPost[];
};

export default function Home({
    sliders,
    featuredProducts,
    newProducts,
    flashSaleProducts,
    flashSaleEndsAt,
    latestPosts,
}: Props) {
    return (
        <GuestLayout>
            <Head title="Beranda" />

            <HeroSlider sliders={sliders} />

            {flashSaleProducts.length > 0 && (
                <PageContainer>
                    <SectionCard
                        title="Flash Sale"
                        variant="primary"
                        headerExtra={<FlashCountdown endsAt={flashSaleEndsAt} />}
                        action={{ label: 'Lihat Semua →', href: '/products' }}
                    >
                        <div className="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
                            {flashSaleProducts.map((p) => (
                                <div key={p.id} className="w-[160px] shrink-0">
                                    <ProductCard product={p} compact />
                                </div>
                            ))}
                        </div>
                    </SectionCard>
                </PageContainer>
            )}

            {featuredProducts.length > 0 && (
                <PageContainer>
                    <ProductSection title="Produk Unggulan" products={featuredProducts} />
                </PageContainer>
            )}

            {newProducts.length > 0 && (
                <PageContainer>
                    <ProductSection title="Produk Terbaru" products={newProducts} />
                </PageContainer>
            )}

            {latestPosts.length > 0 && (
                <PageContainer>
                    <SectionCard title="Blog Terbaru" action={{ label: 'Lihat Semua →', href: '/blog' }}>
                        <div className="grid md:grid-cols-3 gap-4">
                            {latestPosts.map((post) => (
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
            )}
        </GuestLayout>
    );
}

function ProductSection({ title, products }: { title: string; products: ProductCardData[] }) {
    return (
        <SectionCard title={title} action={{ label: 'Lihat Semua →', href: '/products' }}>
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                {products.map((p) => (
                    <ProductCard key={p.id} product={p} />
                ))}
            </div>
        </SectionCard>
    );
}

function FlashCountdown({ endsAt }: { endsAt: number }) {
    const remaining = Math.max(0, endsAt - Math.floor(Date.now() / 1000));
    const h = Math.floor(remaining / 3600);
    const m = Math.floor((remaining % 3600) / 60);
    const s = remaining % 60;
    return (
        <span className="font-mono text-xs bg-primary/10 text-primary px-2 py-1 rounded">
            {String(h).padStart(2, '0')}:{String(m).padStart(2, '0')}:{String(s).padStart(2, '0')}
        </span>
    );
}
