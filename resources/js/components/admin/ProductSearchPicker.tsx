import { useCallback, useState } from 'react';
import { PackagePlus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/utils';
import { FetchError, fetchJson } from '@/lib/fetchJson';

export type SearchProduct = {
    id: number;
    name: string;
    sku: string;
    price?: number;
    imageUrl?: string;
};

export type SelectedProduct = SearchProduct;

type Props = {
    selected: SelectedProduct[];
    onChange: (products: SelectedProduct[]) => void;
    excludeProductId?: number;
    searchUrl?: string;
    label?: string;
};

export function ProductSearchPicker({
    selected,
    onChange,
    excludeProductId,
    searchUrl = '/admin/products/search',
    label = 'Cari produk',
}: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchProduct[]>([]);
    const [searching, setSearching] = useState(false);
    const [open, setOpen] = useState(false);

    const [searchError, setSearchError] = useState<string | null>(null);

    const search = useCallback(async (value: string) => {
        if (!value.trim()) {
            setResults([]);
            setSearchError(null);
            return;
        }

        setSearching(true);
        setSearchError(null);
        try {
            const params = new URLSearchParams({ q: value });
            if (excludeProductId) {
                params.set('exclude', String(excludeProductId));
            }
            const data = await fetchJson<{ products?: SearchProduct[] }>(`${searchUrl}?${params.toString()}`);
            setResults(data.products ?? []);
        } catch (error) {
            setResults([]);
            setSearchError(error instanceof FetchError ? error.message : 'Gagal mencari produk.');
        } finally {
            setSearching(false);
        }
    }, [excludeProductId, searchUrl]);

    const addProduct = (product: SearchProduct) => {
        if (selected.some((item) => item.id === product.id)) {
            return;
        }

        onChange([...selected, product]);
        setQuery('');
        setResults([]);
        setOpen(false);
    };

    const removeProduct = (productId: number) => {
        onChange(selected.filter((item) => item.id !== productId));
    };

    return (
        <div className="space-y-3">
            {open ? (
                <div className="space-y-2 rounded-md border p-3">
                    <Label htmlFor="product-search">{label}</Label>
                    <Input
                        id="product-search"
                        placeholder="Ketik nama atau SKU..."
                        value={query}
                        autoFocus
                        onChange={(e) => {
                            setQuery(e.target.value);
                            search(e.target.value);
                        }}
                    />
                    {searching && <p className="text-xs text-muted-foreground">Mencari...</p>}
                    {searchError && <p className="text-xs text-destructive">{searchError}</p>}
                    {results.length > 0 && (
                        <div className="max-h-40 overflow-y-auto rounded-md border">
                            {results.map((product) => (
                                <button
                                    key={product.id}
                                    type="button"
                                    className="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-muted"
                                    onClick={() => addProduct(product)}
                                >
                                    {product.imageUrl ? (
                                        <img
                                            src={product.imageUrl}
                                            alt=""
                                            className="h-8 w-8 rounded object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-8 w-8 items-center justify-center rounded bg-muted text-xs">
                                            —
                                        </div>
                                    )}
                                    <span className="min-w-0 flex-1 truncate">
                                        {product.name}
                                        <span className="block text-xs text-muted-foreground">{product.sku}</span>
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}
                    <Button type="button" variant="outline" size="sm" onClick={() => setOpen(false)}>
                        Tutup
                    </Button>
                </div>
            ) : (
                <Button type="button" variant="outline" size="sm" onClick={() => setOpen(true)}>
                    <PackagePlus className="mr-1 size-4" />
                    Tambahkan Produk
                </Button>
            )}

            {selected.length > 0 ? (
                <div className="grid gap-2 sm:grid-cols-2">
                    {selected.map((product) => (
                        <div
                            key={product.id}
                            className="flex items-center gap-3 rounded-md border p-2"
                        >
                            {product.imageUrl ? (
                                <img
                                    src={product.imageUrl}
                                    alt=""
                                    className="h-12 w-12 rounded object-cover"
                                />
                            ) : (
                                <div className="flex h-12 w-12 items-center justify-center rounded bg-muted text-xs">
                                    —
                                </div>
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">{product.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{product.sku}</p>
                                {product.price !== undefined && (
                                    <p className="text-xs text-primary">{formatRupiah(product.price)}</p>
                                )}
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeProduct(product.id)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="rounded-md border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
                    <PackagePlus className="mx-auto mb-2 size-8 opacity-40" />
                    <p>Belum ada produk dipilih.</p>
                </div>
            )}
        </div>
    );
}
