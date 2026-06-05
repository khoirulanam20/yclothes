import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RichTextEditor } from '@/components/admin/RichTextEditor';
import { Card, CardContent } from '@/components/ui/card';

type BlogPost = {
    id: number;
    title: string;
    slug: string;
    content?: string | null;
    excerpt?: string | null;
    featuredImageUrl?: string | null;
    author?: string | null;
    status?: string;
    publishedAt?: string | null;
};

type Props = {
    post?: BlogPost;
};

export default function Form({ post }: Props) {
    const isEdit = !!post?.id;
    const { data, setData, post: submitForm, transform, processing, errors } = useForm({
        title: post?.title ?? '',
        slug: post?.slug ?? '',
        content: post?.content ?? '',
        excerpt: post?.excerpt ?? '',
        featured_image: null as File | null,
        remove_featured_image: false,
        author: post?.author ?? '',
        status: post?.status ?? 'draft',
        published_at: post?.publishedAt?.slice(0, 16) ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true as const, preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            submitForm(`/admin/blog-posts/${post!.id}`, options);
        } else {
            submitForm('/admin/blog-posts', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Artikel' : 'Tambah Artikel'}
            breadcrumbs={[
                { label: 'Blog', href: '/admin/blog-posts' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Artikel' : 'Tambah Artikel'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Artikel' : 'Tambah Artikel'}
                backHref="/admin/blog-posts"
            />
            <Card>
                <CardContent className="p-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    required
                                />
                                <FieldError message={errors.title} />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                        <div className="grid md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                />
                                <FieldError message={errors.slug} />
                            </div>
                            <div>
                                <Label htmlFor="author">Penulis</Label>
                                <Input
                                    id="author"
                                    value={data.author}
                                    onChange={(e) => setData('author', e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="excerpt">Excerpt</Label>
                            <Textarea
                                id="excerpt"
                                rows={2}
                                value={data.excerpt}
                                onChange={(e) => setData('excerpt', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label htmlFor="content">Konten</Label>
                            <RichTextEditor
                                value={data.content}
                                onChange={(html) => setData('content', html)}
                                minHeight={400}
                            />
                            <FieldError message={errors.content} />
                        </div>
                        <div>
                            <Label htmlFor="featured_image">Featured Image</Label>
                            <Input
                                id="featured_image"
                                type="file"
                                accept="image/*"
                                onChange={(e) => setData('featured_image', e.target.files?.[0] ?? null)}
                            />
                            {isEdit && post?.featuredImageUrl && (
                                <div className="mt-2">
                                    <img src={post.featuredImageUrl} alt="" className="h-24 rounded" />
                                    <label className="flex items-center gap-2 text-sm mt-1">
                                        <input
                                            type="checkbox"
                                            checked={data.remove_featured_image}
                                            onChange={(e) =>
                                                setData('remove_featured_image', e.target.checked)
                                            }
                                        />
                                        Hapus gambar
                                    </label>
                                </div>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="published_at">Published At</Label>
                            <Input
                                id="published_at"
                                type="datetime-local"
                                value={data.published_at}
                                onChange={(e) => setData('published_at', e.target.value)}
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Simpan
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/admin/blog-posts">Batal</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
