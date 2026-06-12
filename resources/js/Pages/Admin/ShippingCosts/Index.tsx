import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { ShippingRegencySelect } from '@/components/admin/ShippingRegencySelect';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';
import { formatRupiah } from '@/lib/utils';
import { CONFIGURATION_HREF, configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type Courier = { code: string; name: string };

type ShippingCost = {
    id: number;
    courierName?: string | null;
    courierCode?: string | null;
    provinceName?: string | null;
    regencyName?: string | null;
    regencyCode?: string | null;
    cost: number;
    costPerKg?: number | null;
    isActive?: boolean;
};

type Filters = {
    search?: string;
    courier_code?: string;
    province_code?: string;
    regency_code?: string;
    status?: string;
};

type Props = {
    costs: Paginated<ShippingCost>;
    couriers: Courier[];
    filters: Filters;
};

const selectClass = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm';

export default function Index({ costs, couriers, filters }: Props) {
    const confirm = useAdminConfirm();
    const [selected, setSelected] = useState<number[]>([]);
    const [bulkProcessing, setBulkProcessing] = useState(false);

    const { data, setData, processing } = useForm({
        search: filters.search ?? '',
        courier_code: filters.courier_code ?? '',
        province_code: filters.province_code ?? '',
        regency_code: filters.regency_code ?? '',
        status: filters.status ?? '',
    });

    const [wilayahNames, setWilayahNames] = useState({
        provinceName: '',
        regencyName: '',
    });

    const pageIds = costs.data.map((c) => c.id);
    const allPageSelected = pageIds.length > 0 && pageIds.every((id) => selected.includes(id));
    const somePageSelected = pageIds.some((id) => selected.includes(id));

    useEffect(() => {
        setSelected([]);
    }, [costs.meta.current_page, filters.search, filters.courier_code, filters.province_code, filters.regency_code, filters.status]);

    const submitFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/shipping-costs', {
            search: data.search || undefined,
            courier_code: data.courier_code || undefined,
            province_code: data.province_code || undefined,
            regency_code: data.regency_code || undefined,
            status: data.status || undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        router.get('/admin/shipping-costs', {}, { preserveState: true, preserveScroll: true });
    };

    const toggleAllPage = () => {
        if (allPageSelected) {
            setSelected((prev) => prev.filter((id) => !pageIds.includes(id)));
        } else {
            setSelected((prev) => [...new Set([...prev, ...pageIds])]);
        }
    };

    const toggleRow = (id: number) => {
        setSelected((prev) => (prev.includes(id) ? prev.filter((v) => v !== id) : [...prev, id]));
    };

    const runBulk = async (action: 'activate' | 'deactivate' | 'delete') => {
        if (selected.length === 0) {
            return;
        }

        const labels = {
            activate: 'aktifkan',
            deactivate: 'nonaktifkan',
            delete: 'hapus',
        };

        const ok = await confirm({
            title: `${action === 'delete' ? 'Hapus' : action === 'activate' ? 'Aktifkan' : 'Nonaktifkan'} ${selected.length} tarif?`,
            description: `Anda akan ${labels[action]} ${selected.length} tarif ongkir terpilih.`,
            confirmLabel: action === 'delete' ? 'Hapus' : 'Ya',
            cancelLabel: 'Batal',
            variant: action === 'delete' ? 'destructive' : 'default',
        });

        if (!ok) {
            return;
        }

        setBulkProcessing(true);
        router.post(
            '/admin/shipping-costs/bulk',
            { action, ids: selected },
            {
                preserveScroll: true,
                onFinish: () => setBulkProcessing(false),
                onSuccess: () => setSelected([]),
            },
        );
    };

    return (
        <AdminLayout title="Ongkos Kirim" breadcrumbs={configurationSectionBreadcrumbs('Tarif Ongkir Manual')}>
            <Head title="Ongkos Kirim" />
            <AdminContent>
                <AdminPageHeader title="Tarif Ongkir Manual" backHref={CONFIGURATION_HREF} createHref="/admin/shipping-costs/create" />

                <Card className="mb-4">
                    <CardContent className="p-4 space-y-4">
                        <form onSubmit={submitFilter} className="space-y-4">
                            <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                                <div className="lg:col-span-2">
                                    <Label htmlFor="search">Cari</Label>
                                    <Input
                                        id="search"
                                        value={data.search}
                                        onChange={(e) => setData('search', e.target.value)}
                                        placeholder="Ekspedisi, provinsi, kota, kode wilayah..."
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="courier_code">Ekspedisi</Label>
                                    <select
                                        id="courier_code"
                                        className={selectClass}
                                        value={data.courier_code}
                                        onChange={(e) => setData('courier_code', e.target.value)}
                                    >
                                        <option value="">Semua ekspedisi</option>
                                        {couriers.map((c) => (
                                            <option key={c.code} value={c.code}>{c.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        className={selectClass}
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        <option value="">Semua status</option>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <ShippingRegencySelect
                                optional
                                value={{
                                    provinceCode: data.province_code,
                                    provinceName: wilayahNames.provinceName,
                                    regencyCode: data.regency_code,
                                    regencyName: wilayahNames.regencyName,
                                }}
                                onChange={(value) => {
                                    setData({
                                        province_code: value.provinceCode,
                                        regency_code: value.regencyCode,
                                    });
                                    setWilayahNames({
                                        provinceName: value.provinceName,
                                        regencyName: value.regencyName,
                                    });
                                }}
                            />
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" disabled={processing}>Filter</Button>
                                <Button type="button" variant="outline" onClick={resetFilters} disabled={processing}>Reset</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {selected.length > 0 && (
                    <div className="mb-4 flex flex-wrap items-center gap-2 rounded-lg border bg-muted/40 px-4 py-3">
                        <span className="text-sm text-muted-foreground">{selected.length} terpilih</span>
                        <Button size="sm" variant="outline" disabled={bulkProcessing} onClick={() => void runBulk('activate')}>
                            Aktifkan
                        </Button>
                        <Button size="sm" variant="outline" disabled={bulkProcessing} onClick={() => void runBulk('deactivate')}>
                            Nonaktifkan
                        </Button>
                        <Button size="sm" variant="destructive" disabled={bulkProcessing} onClick={() => void runBulk('delete')}>
                            Hapus
                        </Button>
                    </div>
                )}

                <Card>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10">
                                            <input
                                                type="checkbox"
                                                className="size-4 rounded border-input"
                                                checked={allPageSelected}
                                                ref={(el) => {
                                                    if (el) {
                                                        el.indeterminate = somePageSelected && !allPageSelected;
                                                    }
                                                }}
                                                onChange={toggleAllPage}
                                                aria-label="Pilih semua di halaman ini"
                                            />
                                        </TableHead>
                                        <TableHead>Ekspedisi</TableHead>
                                        <TableHead>Provinsi</TableHead>
                                        <TableHead>Kab/Kota</TableHead>
                                        <TableHead>Kode Wilayah</TableHead>
                                        <TableHead>Ongkir</TableHead>
                                        <TableHead>Per Kg</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {costs.data.map((c) => (
                                        <TableRow key={c.id} data-state={selected.includes(c.id) ? 'selected' : undefined}>
                                            <TableCell>
                                                <input
                                                    type="checkbox"
                                                    className="size-4 rounded border-input"
                                                    checked={selected.includes(c.id)}
                                                    onChange={() => toggleRow(c.id)}
                                                    aria-label={`Pilih ${c.regencyName ?? c.id}`}
                                                />
                                            </TableCell>
                                            <TableCell>{c.courierName ?? c.courierCode ?? '—'}</TableCell>
                                            <TableCell>{c.provinceName ?? '—'}</TableCell>
                                            <TableCell>{c.regencyName ?? '—'}</TableCell>
                                            <TableCell className="font-mono text-xs">{c.regencyCode ?? '—'}</TableCell>
                                            <TableCell>{formatRupiah(c.cost)}</TableCell>
                                            <TableCell>{c.costPerKg ? formatRupiah(c.costPerKg) : '—'}</TableCell>
                                            <TableCell>
                                                <Badge variant={c.isActive ? 'default' : 'secondary'}>
                                                    {c.isActive ? 'Aktif' : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <AdminTableActions>
                                                    <AdminEditAction href={`/admin/shipping-costs/${c.id}/edit`} />
                                                    <DeleteRecordButton href={`/admin/shipping-costs/${c.id}`} name={c.regencyName ?? String(c.id)} />
                                                </AdminTableActions>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {costs.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={9} className="py-8 text-center text-muted-foreground">
                                                Tidak ada tarif ongkir yang cocok dengan filter.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
                <PaginationLinks pagination={costs} />
            </AdminContent>
        </AdminLayout>
    );
}
