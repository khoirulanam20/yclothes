import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import {
    AdminApproveAction,
    AdminRejectAction,
    AdminTableActions,
    AdminTableAction,
} from '@/components/admin/AdminTableActions';
import { ReviewDetailModal, type AdminReview } from '@/components/admin/ReviewDetailModal';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';
import { Eye } from 'lucide-react';

type Props = { reviews: Paginated<AdminReview>; status: string; pendingCount?: number };

export default function Index({ reviews, status, pendingCount = 0 }: Props) {
    const confirm = useAdminConfirm();
    const [detailReview, setDetailReview] = useState<AdminReview | null>(null);

    const tabs = [
        { key: 'pending', label: pendingCount > 0 ? `Pending (${pendingCount})` : 'Pending', href: '/admin/reviews?status=pending' },
        { key: 'approved', label: 'Disetujui', href: '/admin/reviews?status=approved' },
    ];

    const rejectReview = async (review: AdminReview) => {
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
            <AdminContent>
            <AdminPageHeader title="Ulasan" />
            <div className="flex gap-2 mb-4">
                {tabs.map((tab) => (
                    <Button key={tab.key} variant={status === tab.key ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={tab.href}>{tab.label}</Link>
                    </Button>
                ))}
            </div>
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table>
                    <TableHeader><TableRow><TableHead>Produk</TableHead><TableHead>Customer</TableHead><TableHead>Rating</TableHead><TableHead>Komentar</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>
                        {reviews.data.map((review) => (
                            <TableRow key={review.id}>
                                <TableCell>{review.product?.name ?? '—'}</TableCell>
                                <TableCell>{review.customerName}</TableCell>
                                <TableCell><Badge>{review.rating}★</Badge></TableCell>
                                <TableCell>
                                    <div className="max-w-xs">
                                        {review.imagesUrl && review.imagesUrl.length > 0 && (
                                            <div className="mb-1 flex items-center gap-1">
                                                <div className="flex gap-1">
                                                    {review.imagesUrl.slice(0, 2).map((url, index) => (
                                                        <img key={index} src={url} alt="" className="size-8 rounded object-cover" />
                                                    ))}
                                                </div>
                                                {review.imagesUrl.length > 2 && (
                                                    <Badge variant="secondary" className="text-[10px]">
                                                        +{review.imagesUrl.length - 2}
                                                    </Badge>
                                                )}
                                            </div>
                                        )}
                                        <span className="line-clamp-2 text-sm">{review.comment || '—'}</span>
                                    </div>
                                </TableCell>
                                <TableCell className="text-right">
                                    <AdminTableActions>
                                        <AdminTableAction
                                            label="Detail"
                                            icon={Eye}
                                            onClick={() => setDetailReview(review)}
                                        />
                                        {status === 'pending' && (
                                            <>
                                                <AdminApproveAction onClick={() => router.post(`/admin/reviews/${review.id}/approve`, {}, { preserveScroll: true })} />
                                                <AdminRejectAction onClick={() => void rejectReview(review)} />
                                            </>
                                        )}
                                    </AdminTableActions>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={reviews} />

            <ReviewDetailModal
                review={detailReview}
                open={!!detailReview}
                onOpenChange={(open) => !open && setDetailReview(null)}
                status={status}
                onReject={rejectReview}
            />
            </AdminContent>
        </AdminLayout>
    );
}
