import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';

export type InventoryRow = {
    warehouseId: number;
    warehouseName: string;
    stock: number;
    lowStockThreshold: number;
};

type WarehouseOption = { id: number; name: string };

type Props = {
    rows: InventoryRow[];
    warehouses: WarehouseOption[];
    onChange: (rows: InventoryRow[]) => void;
    errors?: Record<string, string>;
    compact?: boolean;
};

export function ProductInventoryTable({ rows, warehouses, onChange, errors, compact }: Props) {
    if (warehouses.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Belum ada gudang aktif.{' '}
                <Link href="/admin/warehouses" className="text-primary underline">
                    Kelola gudang
                </Link>
            </p>
        );
    }

    const updateRow = (index: number, patch: Partial<InventoryRow>) => {
        const next = [...rows];
        next[index] = { ...next[index], ...patch };
        onChange(next);
    };

    return (
        <div className={compact ? 'space-y-2' : 'space-y-3'}>
            {!compact && <Label>Stok per gudang</Label>}
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full min-w-[480px] text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Gudang</th>
                            <th className="px-3 py-2 text-left">Stok</th>
                            <th className="px-3 py-2 text-left">Batas stok rendah</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={row.warehouseId} className="border-t">
                                <td className="px-3 py-2">{row.warehouseName}</td>
                                <td className="px-3 py-2">
                                    <Input
                                        type="number"
                                        min={0}
                                        value={row.stock}
                                        onChange={(e) =>
                                            updateRow(index, { stock: Number(e.target.value) })
                                        }
                                    />
                                    {errors?.[`inventories.${index}.stock`] && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors[`inventories.${index}.stock`]}
                                        </p>
                                    )}
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        type="number"
                                        min={0}
                                        value={row.lowStockThreshold}
                                        onChange={(e) =>
                                            updateRow(index, {
                                                lowStockThreshold: Number(e.target.value),
                                            })
                                        }
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export function inventoryRowsToPayload(rows: InventoryRow[]) {
    return rows.map((row) => ({
        warehouse_id: row.warehouseId,
        stock: row.stock,
        low_stock_threshold: row.lowStockThreshold,
    }));
}

export function buildInventoryRows(
    warehouses: WarehouseOption[],
    existing?: InventoryRow[],
): InventoryRow[] {
    const existingByWarehouse = new Map(
        (existing ?? []).map((row) => [row.warehouseId, row]),
    );

    return warehouses.map((warehouse) => {
        const row = existingByWarehouse.get(warehouse.id);

        return {
            warehouseId: warehouse.id,
            warehouseName: warehouse.name,
            stock: row?.stock ?? 0,
            lowStockThreshold: row?.lowStockThreshold ?? 5,
        };
    });
}
