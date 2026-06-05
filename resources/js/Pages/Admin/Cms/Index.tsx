import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
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

type CmsPage = {
    id: number;
    title: string;
    slug: string;
    status: string;
    hasLayout: boolean;
};

type Props = {
    pages: Paginated<CmsPage>;
};

export default function Index({ pages }: Props) {
    return (
        <AdminLayout title="Halaman CMS" breadcrumbs={[{ label: 'Halaman' }]}>
            <Head title="Halaman CMS" />
            <AdminPageHeader title="Halaman CMS" createHref="/admin/cms-pages/builder/new" />

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Judul</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Layout</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pages.data.map((page) => (
                                <TableRow key={page.id}>
                                    <TableCell className="font-medium">{page.title}</TableCell>
                                    <TableCell>
                                        <code className="text-xs">{page.slug}</code>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={page.status === 'published' ? 'default' : 'secondary'}>
                                            {page.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={page.hasLayout ? 'default' : 'outline'}>
                                            {page.hasLayout ? 'Puck' : 'Kosong'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/cms-pages/${page.id}/builder`}>Builder</Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/cms-pages/${page.id}/preview`} target="_blank">
                                                    Preview
                                                </Link>
                                            </Button>
                                            <DeleteRecordButton
                                                href={`/admin/cms-pages/${page.id}`}
                                                name={page.title}
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <PaginationLinks pagination={pages} />
        </AdminLayout>
    );
}
