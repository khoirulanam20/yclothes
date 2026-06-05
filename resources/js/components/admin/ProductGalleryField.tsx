import { X } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/admin/FieldError';

export type GalleryItem = {
    path: string;
    url: string;
    file?: File;
};

type Props = {
    mainImageUrl?: string;
    mainImageFile: File | null;
    removeMainImage: boolean;
    onMainImageChange: (file: File | null) => void;
    onRemoveMainImage: (remove: boolean) => void;
    gallery: GalleryItem[];
    onAddGallery: (files: FileList | null) => void;
    onRemoveGallery: (path: string) => void;
    errors?: Record<string, string>;
};

export function ProductGalleryField({
    mainImageUrl,
    mainImageFile,
    removeMainImage,
    onMainImageChange,
    onRemoveMainImage,
    gallery,
    onAddGallery,
    onRemoveGallery,
    errors = {},
}: Props) {
    const mainPreview = mainImageFile
        ? URL.createObjectURL(mainImageFile)
        : removeMainImage
          ? null
          : mainImageUrl;

    return (
        <div className="space-y-6">
            <div>
                <Label htmlFor="image">Gambar Utama</Label>
                <Input
                    id="image"
                    type="file"
                    accept="image/*"
                    className="mt-1"
                    onChange={(e) => onMainImageChange(e.target.files?.[0] ?? null)}
                />
                {mainPreview && (
                    <div className="mt-2 flex items-center gap-3">
                        <img src={mainPreview} alt="" className="h-24 rounded object-cover" />
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={removeMainImage}
                                onChange={(e) => onRemoveMainImage(e.target.checked)}
                            />
                            Hapus gambar utama
                        </label>
                    </div>
                )}
                <FieldError message={errors.image} />
            </div>

            <div>
                <Label htmlFor="gallery">Galeri</Label>
                <Input
                    id="gallery"
                    type="file"
                    accept="image/*"
                    multiple
                    className="mt-1"
                    onChange={(e) => {
                        onAddGallery(e.target.files);
                        e.target.value = '';
                    }}
                />
                {gallery.length > 0 && (
                    <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {gallery.map((item) => (
                            <div key={item.path} className="relative">
                                <img
                                    src={item.file ? URL.createObjectURL(item.file) : item.url}
                                    alt=""
                                    className="aspect-square w-full rounded object-cover"
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="destructive"
                                    className="absolute right-1 top-1 size-7"
                                    onClick={() => onRemoveGallery(item.path)}
                                >
                                    <X className="size-3.5" />
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
