import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type PageSettings = {
    title: string;
    slug: string;
    status: string;
    meta_title: string;
    meta_description: string;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    data: PageSettings;
    errors: Partial<Record<keyof PageSettings | 'layout_json', string>>;
    isNew: boolean;
    onChange: (field: keyof PageSettings, value: string) => void;
    onSave: () => void;
    processing: boolean;
};

export function CmsPageSettingsDialog({
    open,
    onOpenChange,
    data,
    errors,
    isNew,
    onChange,
    onSave,
    processing,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pengaturan Halaman</DialogTitle>
                    <DialogDescription>Judul, slug, status publikasi, dan metadata SEO.</DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div>
                        <Label htmlFor="cms-title">Judul</Label>
                        <Input
                            id="cms-title"
                            value={data.title}
                            onChange={(e) => onChange('title', e.target.value)}
                            required
                        />
                        <FieldError message={errors.title} />
                    </div>
                    <div>
                        <Label htmlFor="cms-slug">Slug</Label>
                        <Input
                            id="cms-slug"
                            value={data.slug}
                            onChange={(e) => onChange('slug', e.target.value)}
                            placeholder="tentang-kami"
                        />
                        <FieldError message={errors.slug} />
                        {isNew && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                Kosongkan untuk generate otomatis dari judul.
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="cms-status">Status</Label>
                        <select
                            id="cms-status"
                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={data.status}
                            onChange={(e) => onChange('status', e.target.value)}
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                        <FieldError message={errors.status} />
                    </div>
                    <div>
                        <Label htmlFor="cms-meta-title">Meta Title</Label>
                        <Input
                            id="cms-meta-title"
                            value={data.meta_title}
                            onChange={(e) => onChange('meta_title', e.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="cms-meta-description">Meta Description</Label>
                        <Textarea
                            id="cms-meta-description"
                            rows={3}
                            value={data.meta_description}
                            onChange={(e) => onChange('meta_description', e.target.value)}
                        />
                    </div>
                    <FieldError message={errors.layout_json} />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Tutup
                    </Button>
                    <Button type="button" onClick={onSave} disabled={processing}>
                        Simpan Halaman
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
