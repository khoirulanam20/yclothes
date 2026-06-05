import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';

type BlogPost = {
    id: number; title: string; slug: string; content?: string | null;
    excerpt?: string | null; featuredImageUrl?: string | null;
    publishedAt?: string | null; author?: string | null;
};

type Props = { post: BlogPost };

export default function Show({ post }: Props) {
    return (
        <GuestLayout>
            <Head title={post.title} />
            <PageContainer narrow>
                <Breadcrumb
                    items={[
                        { label: 'Beranda', href: '/' },
                        { label: 'Blog', href: '/blog' },
                        { label: post.title },
                    ]}
                />

                <SectionCard noPadding>
                    {post.featuredImageUrl && (
                        <img
                            src={post.featuredImageUrl}
                            alt=""
                            className="w-full aspect-video object-cover"
                        />
                    )}
                    <div className="p-6">
                        <h1 className="text-2xl font-bold mb-2">{post.title}</h1>
                        <p className="text-sm text-muted-foreground mb-6">
                            {post.author && `${post.author} · `}
                            {post.publishedAt && new Date(post.publishedAt).toLocaleDateString('id-ID')}
                        </p>
                        {post.content && (
                            <div
                                className="prose prose-neutral max-w-none prose-sm"
                                dangerouslySetInnerHTML={{ __html: post.content }}
                            />
                        )}
                        <Button variant="ghost" size="sm" className="mt-6" asChild>
                            <Link href="/blog">← Kembali ke Blog</Link>
                        </Button>
                    </div>
                </SectionCard>
            </PageContainer>
        </GuestLayout>
    );
}
