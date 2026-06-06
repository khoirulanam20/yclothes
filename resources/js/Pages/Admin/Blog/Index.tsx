import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type BlogPost = {
    id: number;
    title: string;
    slug: string;
    author?: string | null;
    publishedAt?: string | null;
    status?: string;
};

type Props = {
    posts: Paginated<BlogPost>;
};

export default function Index({ posts }: Props) {
    return (
        <AdminLayout title="Blog" breadcrumbs={[{ label: 'Blog' }]}>
            <Head title="Blog" />
            <AdminContent>
            <AdminPageHeader title="Blog" createHref="/admin/blog-posts/create" />

            <Card>
                <CardContent className="p-0">
                    <AdminTableScroll>
                        <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Judul</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Penulis</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.data.map((post) => (
                                <TableRow key={post.id}>
                                    <TableCell className="font-medium">{post.title}</TableCell>
                                    <TableCell>
                                        <code className="text-xs">{post.slug}</code>
                                    </TableCell>
                                    <TableCell>{post.author ?? '—'}</TableCell>
                                    <TableCell>
                                        <Badge variant={post.status === 'published' ? 'default' : 'secondary'}>
                                            {post.status ?? 'draft'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/blog-posts/${post.id}/edit`}>Edit</Link>
                                            </Button>
                                            <DeleteRecordButton
                                                href={`/admin/blog-posts/${post.id}`}
                                                name={post.title}
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    </AdminTableScroll>
                </CardContent>
            </Card>

            <PaginationLinks pagination={posts} />
            </AdminContent>
        </AdminLayout>
    );
}
