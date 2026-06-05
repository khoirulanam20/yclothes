import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';

type Review = {
    id: number; rating: number; comment: string; customerName: string; createdAt?: string;
    product?: { id: number; name: string } | null; isApproved?: boolean;
};

type Props = { reviews: Paginated<Review>; status: string };

export default function Index({ reviews, status }: Props) {
    const confirm = useAdminConfirm();

    const tabs = [
        { key: 'pending', label: 'Pending', href: '/admin/reviews?status=pending' },
        { key: 'approved', label: 'Disetujui', href: '/admin/reviews?status=approved' },
    ];

    const rejectReview = async (review: Review) => {
        const ok = await confirm({
            title: 'Tolak ulasan?',
            description: `Ulasan dari ${review.customerName} akan dihapus.`,
            confirmLabel: 'Tolak',
            cancelLabel: 'Batal',
            variant: 'destructive',
        });

        if (ok) {
            router.delete(`/admin/reviews/${review.id}/reject`, { preserveScroll: true });
        }
    };

    return (
        <AdminLayout title="Ulasan" breadcrumbs={[{ label: 'Ulasan' }]}>
            <Head title="Ulasan" />
            <AdminPageHeader title="Ulasan" />
            <div className="flex gap-2 mb-4">
                {tabs.map((tab) => (
                    <Button key={tab.key} variant={status === tab.key ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={tab.href}>{tab.label}</Link>
                    </Button>
                ))}
            </div>
            <Card><CardContent className="p-0">
                <Table>
                    <TableHeader><TableRow><TableHead>Produk</TableHead><TableHead>Customer</TableHead><TableHead>Rating</TableHead><TableHead>Komentar</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>
                        {reviews.data.map((review) => (
                            <TableRow key={review.id}>
                                <TableCell>{review.product?.name ?? '—'}</TableCell>
                                <TableCell>{review.customerName}</TableCell>
                                <TableCell><Badge>{review.rating}★</Badge></TableCell>
                                <TableCell className="max-w-xs truncate">{review.comment}</TableCell>
                                <TableCell><div className="flex gap-1">
                                    {status === 'pending' && <>
                                        <Button size="sm" onClick={() => router.post(`/admin/reviews/${review.id}/approve`, {}, { preserveScroll: true })}>Setujui</Button>
                                        <Button size="sm" variant="destructive" onClick={() => rejectReview(review)}>Tolak</Button>
                                    </>}
                                </div></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent></Card>
            <PaginationLinks pagination={reviews} />
        </AdminLayout>
    );
}
