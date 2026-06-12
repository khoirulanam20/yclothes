import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type CartRule = {
    id: number; name: string; couponCode?: string | null; discountType: string;
    isActive?: boolean; startDate?: string; endDate?: string;
    usesPerCoupon?: number; usesPerCustomer?: number;
    minOrderAmount?: number | null; minQty?: number | null;
};
type Props = { rules: Paginated<CartRule> };

function formatLimit(value?: number, suffix = ''): string {
    if (!value || value <= 0) return '∞';
    return `${value}${suffix}`;
}

const discountTypeLabel: Record<string, string> = {
    percentage: 'Persentase',
    fixed: 'Nominal',
    free_shipping: 'Gratis ongkir',
};

function formatRequirements(rule: CartRule): string {
    const parts: string[] = [];
    if (rule.minQty && rule.minQty > 0) {
        parts.push(`min. ${rule.minQty} item`);
    }
    if (rule.minOrderAmount && rule.minOrderAmount > 0) {
        parts.push(`min. Rp ${rule.minOrderAmount.toLocaleString('id-ID')}`);
    }
    return parts.length > 0 ? parts.join(' · ') : '—';
}

export default function Index({ rules }: Props) {
    return (
        <AdminLayout title="Kupon" breadcrumbs={[{ label: 'Kupon' }]}>
            <Head title="Kupon" />
            <AdminContent>
            <AdminPageHeader title="Kupon" createHref="/admin/cart-rules/create" createLabel="Tambah Kupon" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Kupon</TableHead><TableHead>Tipe</TableHead><TableHead>Syarat</TableHead><TableHead>Batas</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{rules.data.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell>{r.couponCode ?? '—'}</TableCell>
                            <TableCell><Badge variant="outline">{discountTypeLabel[r.discountType] ?? r.discountType}</Badge></TableCell>
                            <TableCell className="text-sm text-muted-foreground">{formatRequirements(r)}</TableCell>
                            <TableCell className="text-sm text-muted-foreground">
                                {r.couponCode ? (
                                    <span>{formatLimit(r.usesPerCoupon, 'x global')} / {formatLimit(r.usesPerCustomer, 'x/pembeli')}</span>
                                ) : '—'}
                            </TableCell>
                            <TableCell>{r.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/cart-rules/${r.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/cart-rules/${r.id}`} name={r.name} />
                                </AdminTableActions>
                            </TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={rules} />
            </AdminContent>
        </AdminLayout>
    );
}
