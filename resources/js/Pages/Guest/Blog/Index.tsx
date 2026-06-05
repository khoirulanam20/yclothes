import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Card, CardContent } from '@/components/ui/card';

type BlogPost = {
    id: number; title: string; slug: string; excerpt?: string | null;
    featuredImageUrl?: string | null; publishedAt?: string | null; author?: string | null;
};
type Props = { posts: Paginated<BlogPost> };

export default function Index({ posts }: Props) {
    return (
        <GuestLayout>
            <Head title="Blog" />
            <PageContainer>
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Blog' }]} />

                <SectionCard title="Blog" noPadding>
                    <div className="p-4">
                        <div className="grid md:grid-cols-3 gap-4">
                            {posts.data.map((post) => (
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
                                            <h2 className="font-medium text-sm line-clamp-2">{post.title}</h2>
                                            {post.excerpt && (
                                                <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                                                    {post.excerpt}
                                                </p>
                                            )}
                                            {post.publishedAt && (
                                                <p className="text-[11px] text-muted-foreground mt-2">
                                                    {new Date(post.publishedAt).toLocaleDateString('id-ID')}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Link>
                                </Card>
                            ))}
                        </div>
                        <PaginationLinks pagination={posts} />
                    </div>
                </SectionCard>
            </PageContainer>
        </GuestLayout>
    );
}
