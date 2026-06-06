import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { SortableSectionList, type LayoutSection } from '@/components/admin/homepage/SortableSectionList';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type SectionType = { value: string; label: string };
type Slider = {
    id: number; title: string | null; imageUrl: string; linkUrl: string | null;
    sortOrder: number; isActive: boolean;
};
type Category = { id: number; name: string };
type Product = { id: number; name: string; sku: string };
type FlashSaleItem = { productId: number; discountType: 'percentage' | 'fixed'; discountAmount: number; productName?: string; sku?: string };
type SearchProduct = Product & { price?: number; salePrice?: number | null };

type Props = {
    layout: LayoutSection[];
    sectionTypes: SectionType[];
    sliders: Slider[];
    categories: Category[];
    products: Product[];
    badgePresets: Record<string, string>;
};

function defaultEndOfDay(): string {
    const d = new Date();
    d.setHours(23, 59, 0, 0);
    return d.toISOString().slice(0, 16);
}

function defaultProps(type: string): Record<string, unknown> {
    switch (type) {
        case 'flash_sale':
            return {
                title: 'Flash Sale', showCountdown: true, endsAt: defaultEndOfDay(),
                actionLabel: 'Lihat Semua →', actionHref: '/products?flash_sale=1',
                items: [] as FlashSaleItem[], metaTitle: '', metaDescription: '',
            };
        case 'category_grid':
            return { title: 'Kategori', limit: 8, columns: 4, showImages: true };
        case 'product_grid':
            return { title: 'Produk', source: 'latest', limit: 8, layout: 'grid', actionLabel: 'Lihat Semua →', actionHref: '/products', productIds: [], badgePreset: 'sale' };
        case 'products_by_category':
            return { title: '', categoryId: 0, limit: 8, layout: 'grid', actionLabel: 'Lihat Semua →', actionHref: '/products' };
        case 'promotion_banner':
            return { title: '', subtitle: '', ctaLabel: '', ctaHref: '', imagePath: '', imageUrl: '', imageAlt: '', metaTitle: '', metaDescription: '' };
        case 'blog_posts':
            return { title: 'Blog Terbaru', limit: 3, actionLabel: 'Lihat Semua →', actionHref: '/blog' };
        case 'spacer':
            return { height: 32 };
        default:
            return {};
    }
}

function SliderForm({ sliders }: { sliders: Slider[] }) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const createForm = useForm({ title: '', image: null as File | null, link_url: '', sort_order: sliders.length, is_active: true });
    const editForm = useForm({ title: '', image: null as File | null, link_url: '', sort_order: 0, is_active: true });

    const startEdit = (slider: Slider) => {
        setEditingId(slider.id);
        editForm.setData({ title: slider.title ?? '', image: null, link_url: slider.linkUrl ?? '', sort_order: slider.sortOrder, is_active: slider.isActive });
    };

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                {sliders.map((slider) => (
                    <div key={slider.id} className="flex items-center gap-3 border rounded-md p-2">
                        <img src={slider.imageUrl} alt="" className="h-12 w-20 object-cover rounded" />
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">{slider.title || 'Tanpa judul'}</p>
                            <p className="text-xs text-muted-foreground">{slider.linkUrl || '—'}</p>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={() => startEdit(slider)}>Edit</Button>
                        <Button type="button" variant="ghost" size="sm" onClick={() => router.delete(`/admin/homepage/sliders/${slider.id}`, { preserveScroll: true })}>
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                ))}
            </div>
            {editingId ? (
                <div className="space-y-3 border-t pt-4">
                    <p className="text-sm font-medium">Edit Slide #{editingId}</p>
                    <div><Label>Judul</Label><Input value={editForm.data.title} onChange={(e) => editForm.setData('title', e.target.value)} /></div>
                    <div><Label>Gambar Baru</Label><Input type="file" accept="image/*" onChange={(e) => editForm.setData('image', e.target.files?.[0] ?? null)} /></div>
                    <div><Label>Link</Label><Input value={editForm.data.link_url} onChange={(e) => editForm.setData('link_url', e.target.value)} /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={editForm.data.is_active} onChange={(e) => editForm.setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2">
                        <Button type="button" disabled={editForm.processing} onClick={() => { editForm.transform((d) => ({ ...d, _method: 'put' })); editForm.post(`/admin/homepage/sliders/${editingId}`, { forceFormData: true, preserveScroll: true, onSuccess: () => setEditingId(null) }); }}>Simpan</Button>
                        <Button type="button" variant="outline" onClick={() => setEditingId(null)}>Batal</Button>
                    </div>
                </div>
            ) : (
                <div className="space-y-3 border-t pt-4">
                    <p className="text-sm font-medium">Tambah Slide</p>
                    <div><Label>Judul</Label><Input value={createForm.data.title} onChange={(e) => createForm.setData('title', e.target.value)} /></div>
                    <div><Label>Gambar</Label><Input type="file" accept="image/*" required onChange={(e) => createForm.setData('image', e.target.files?.[0] ?? null)} /><FieldError message={createForm.errors.image} /></div>
                    <div><Label>Link</Label><Input value={createForm.data.link_url} onChange={(e) => createForm.setData('link_url', e.target.value)} /></div>
                    <Button type="button" disabled={createForm.processing} onClick={() => createForm.post('/admin/homepage/sliders', { forceFormData: true, preserveScroll: true, onSuccess: () => createForm.reset() })}><Plus className="h-4 w-4 mr-1" /> Tambah Slide</Button>
                </div>
            )}
        </div>
    );
}

function FlashSaleItemEditor({ items, onChange }: { items: FlashSaleItem[]; onChange: (items: FlashSaleItem[]) => void }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchProduct[]>([]);
    const [searching, setSearching] = useState(false);

    const search = useCallback(async (q: string) => {
        if (!q.trim()) { setResults([]); return; }
        setSearching(true);
        try {
            const res = await fetch(`/admin/homepage/products/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            setResults(data.products ?? []);
        } finally {
            setSearching(false);
        }
    }, []);

    const addProduct = (product: SearchProduct) => {
        if (items.some((i) => i.productId === product.id)) return;
        onChange([...items, { productId: product.id, discountType: 'percentage', discountAmount: 10, productName: product.name, sku: product.sku }]);
        setQuery('');
        setResults([]);
    };

    const updateItem = (index: number, patch: Partial<FlashSaleItem>) => {
        onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)));
    };

    return (
        <div className="space-y-3">
            <Label>Produk Flash Sale</Label>
            <div className="flex gap-2">
                <Input placeholder="Cari produk..." value={query} onChange={(e) => { setQuery(e.target.value); search(e.target.value); }} />
            </div>
            {searching && <p className="text-xs text-muted-foreground">Mencari...</p>}
            {results.length > 0 && (
                <div className="border rounded-md max-h-32 overflow-y-auto">
                    {results.map((p) => (
                        <button key={p.id} type="button" className="w-full text-left px-3 py-2 text-sm hover:bg-muted" onClick={() => addProduct(p)}>
                            {p.name} ({p.sku})
                        </button>
                    ))}
                </div>
            )}
            <div className="space-y-2">
                {items.map((item, index) => (
                    <div key={item.productId} className="grid grid-cols-12 gap-2 items-end border rounded-md p-2">
                        <div className="col-span-5 text-sm truncate">{item.productName ?? `Produk #${item.productId}`}</div>
                        <div className="col-span-3">
                            <select className="w-full h-9 rounded-md border px-2 text-sm" value={item.discountType} onChange={(e) => updateItem(index, { discountType: e.target.value as 'percentage' | 'fixed' })}>
                                <option value="percentage">%</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div className="col-span-3">
                            <Input type="number" min={0} value={item.discountAmount} onChange={(e) => updateItem(index, { discountAmount: Number(e.target.value) })} />
                        </div>
                        <div className="col-span-1">
                            <Button type="button" variant="ghost" size="icon" onClick={() => onChange(items.filter((_, i) => i !== index))}><Trash2 className="h-4 w-4" /></Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function SectionEditor({
    section, onChange, categories, products, badgePresets, sliders, sectionId,
}: {
    section: LayoutSection;
    onChange: (props: Record<string, unknown>) => void;
    categories: Category[];
    products: Product[];
    badgePresets: Record<string, string>;
    sliders: Slider[];
    sectionId: string;
}) {
    const props = section.props;
    const [uploading, setUploading] = useState(false);

    if (section.type === 'hero_slider') {
        return <SliderForm sliders={sliders} />;
    }

    const field = (key: string, label: string, type: 'text' | 'number' | 'checkbox' | 'textarea' | 'select' | 'datetime-local' = 'text', options?: { value: string; label: string }[]) => {
        if (type === 'checkbox') {
            return (
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={!!props[key]} onChange={(e) => onChange({ ...props, [key]: e.target.checked })} />
                    {label}
                </label>
            );
        }
        if (type === 'textarea') {
            return (
                <div><Label>{label}</Label><Textarea rows={2} value={(props[key] as string) ?? ''} onChange={(e) => onChange({ ...props, [key]: e.target.value })} /></div>
            );
        }
        if (type === 'select' && options) {
            return (
                <div>
                    <Label>{label}</Label>
                    <select className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={(props[key] as string) ?? ''} onChange={(e) => onChange({ ...props, [key]: e.target.value })}>
                        {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                    </select>
                </div>
            );
        }
        return (
            <div>
                <Label>{label}</Label>
                <Input type={type === 'number' ? 'number' : type === 'datetime-local' ? 'datetime-local' : 'text'} value={(props[key] as string | number) ?? ''} onChange={(e) => onChange({ ...props, [key]: type === 'number' ? Number(e.target.value) : e.target.value })} readOnly={key === 'actionHref' && section.type === 'flash_sale'} />
            </div>
        );
    };

    const uploadBanner = async (file: File) => {
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('image', file);
            const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            const token = match ? decodeURIComponent(match[1]) : '';
            const res = await fetch('/admin/homepage/banner-image', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': token },
            });
            const data = await res.json();
            onChange({ ...props, imagePath: data.path, imageUrl: data.url });
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="space-y-3">
            {(section.type === 'flash_sale' || section.type === 'product_grid' || section.type === 'products_by_category' || section.type === 'category_grid' || section.type === 'blog_posts' || section.type === 'promotion_banner') && field('title', 'Judul Section')}
            {section.type === 'flash_sale' && (
                <>
                    <FlashSaleItemEditor items={(props.items as FlashSaleItem[]) ?? []} onChange={(items) => onChange({ ...props, items, actionHref: '/products?flash_sale=1' })} />
                    {field('showCountdown', 'Tampilkan Countdown', 'checkbox')}
                    {field('endsAt', 'Berakhir', 'datetime-local')}
                    {field('actionLabel', 'Label Tombol')}
                    {field('actionHref', 'Link Tombol')}
                    {field('metaTitle', 'Meta Title (SEO)')}
                    {field('metaDescription', 'Meta Description (SEO)', 'textarea')}
                </>
            )}
            {section.type === 'category_grid' && (
                <>{field('limit', 'Jumlah Kategori', 'number')}{field('columns', 'Kolom', 'number')}{field('showImages', 'Tampilkan Gambar', 'checkbox')}</>
            )}
            {section.type === 'product_grid' && (
                <>
                    {field('source', 'Sumber Produk', 'select', [
                        { value: 'featured', label: 'Unggulan (is_featured)' },
                        { value: 'latest', label: 'Terbaru' },
                        { value: 'sale', label: 'Sale' },
                        { value: 'badge', label: 'By Badge' },
                        { value: 'manual', label: 'Manual' },
                    ])}
                    {props.source === 'badge' && field('badgePreset', 'Badge', 'select', Object.entries(badgePresets).filter(([v]) => v !== 'none').map(([value, label]) => ({ value, label })))}
                    {field('limit', 'Limit', 'number')}
                    {field('layout', 'Layout', 'select', [{ value: 'grid', label: 'Grid' }, { value: 'scroll', label: 'Scroll Horizontal' }])}
                    {field('actionLabel', 'Label Tombol')}
                    {field('actionHref', 'Link Tombol')}
                    {props.source === 'manual' && (
                        <div>
                            <Label>Produk (centang)</Label>
                            <div className="max-h-40 overflow-y-auto border rounded-md p-2 space-y-1">
                                {products.map((p) => {
                                    const ids = (props.productIds as number[]) ?? [];
                                    return (
                                        <label key={p.id} className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={ids.includes(p.id)} onChange={(e) => onChange({ ...props, productIds: e.target.checked ? [...ids, p.id] : ids.filter((id) => id !== p.id) })} />
                                            {p.name} ({p.sku})
                                        </label>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </>
            )}
            {section.type === 'products_by_category' && (
                <>
                    <div>
                        <Label>Kategori</Label>
                        <select className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={Number(props.categoryId) || ''} onChange={(e) => onChange({ ...props, categoryId: Number(e.target.value) })}>
                            <option value="">Pilih kategori</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    {field('limit', 'Limit', 'number')}{field('actionLabel', 'Label Tombol')}{field('actionHref', 'Link Tombol')}
                </>
            )}
            {section.type === 'promotion_banner' && (
                <>
                    {field('subtitle', 'Subtitle')}{field('ctaLabel', 'Label CTA')}{field('ctaHref', 'Link CTA')}
                    <div>
                        <Label>Gambar Banner</Label>
                        <Input type="file" accept="image/*" disabled={uploading} onChange={(e) => { const f = e.target.files?.[0]; if (f) uploadBanner(f); }} />
                        {typeof props.imageUrl === 'string' && props.imageUrl && <img src={props.imageUrl} alt="" className="mt-2 h-24 rounded object-cover" />}
                    </div>
                    {field('imageAlt', 'Alt Text Gambar')}{field('metaTitle', 'Meta Title (SEO)')}{field('metaDescription', 'Meta Description (SEO)', 'textarea')}
                </>
            )}
            {section.type === 'blog_posts' && (
                <>{field('limit', 'Limit', 'number')}{field('actionLabel', 'Label Tombol')}{field('actionHref', 'Link Tombol')}</>
            )}
            {section.type === 'spacer' && field('height', 'Tinggi (px)', 'number')}
        </div>
    );
}

export default function Builder({ layout: initialLayout, sectionTypes, sliders, categories, products, badgePresets }: Props) {
    const [sections, setSections] = useState<LayoutSection[]>(initialLayout);
    const [selectedId, setSelectedId] = useState<string | null>(sections[0]?.id ?? null);
    const [saving, setSaving] = useState(false);
    const selected = sections.find((s) => s.id === selectedId);

    const addSection = (type: string) => {
        const id = `${type}-${Date.now()}`;
        setSections((prev) => [...prev, { id, type, enabled: true, props: defaultProps(type) }]);
        setSelectedId(id);
    };

    const submit = () => {
        setSaving(true);
        router.put('/admin/homepage', { layout: JSON.parse(JSON.stringify(sections)) }, { preserveScroll: true, onFinish: () => setSaving(false) });
    };

    const typeLabel = (type: string) => sectionTypes.find((t) => t.value === type)?.label ?? type;

    return (
        <AdminLayout title="Halaman Utama" breadcrumbs={[{ label: 'Halaman Utama' }]}>
            <Head title="Halaman Utama" />
            <AdminPageHeader title="Halaman Utama" description="Atur urutan dan konfigurasi section beranda." />
            <div className="space-y-6">
                <div className="grid lg:grid-cols-5 gap-6">
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Section</CardTitle>
                            <select className="text-sm border rounded-md px-2 py-1" defaultValue="" onChange={(e) => { if (e.target.value) { addSection(e.target.value); e.target.value = ''; } }}>
                                <option value="">+ Tambah</option>
                                {sectionTypes.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                            </select>
                        </CardHeader>
                        <CardContent>
                            <SortableSectionList
                                sections={sections}
                                selectedId={selectedId}
                                typeLabel={typeLabel}
                                onSelect={setSelectedId}
                                onReorder={setSections}
                                onToggleEnabled={(id, enabled) => setSections((prev) => prev.map((s) => (s.id === id ? { ...s, enabled } : s)))}
                                onRemove={(id) => { setSections((prev) => prev.filter((s) => s.id !== id)); if (selectedId === id) setSelectedId(sections.find((s) => s.id !== id)?.id ?? null); }}
                            />
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-3">
                        <CardHeader><CardTitle>Konfigurasi Section</CardTitle></CardHeader>
                        <CardContent>
                            {selected ? (
                                <SectionEditor section={selected} categories={categories} products={products} badgePresets={badgePresets} sliders={sliders} sectionId={selected.id} onChange={(props) => setSections((prev) => prev.map((s) => (s.id === selected.id ? { ...s, props } : s)))} />
                            ) : (
                                <p className="text-sm text-muted-foreground">Pilih section untuk mengedit.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
                <Button type="button" disabled={saving} onClick={submit}>Simpan Layout</Button>
            </div>
        </AdminLayout>
    );
}
