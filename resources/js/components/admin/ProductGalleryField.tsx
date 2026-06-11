import { ImagePlus, X } from 'lucide-react';
import { useRef, useCallback } from 'react';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/admin/FieldError';
import { cn } from '@/lib/utils';

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
    onAddGallery: (files: File[]) => void;
    onRemoveGallery: (path: string) => void;
    errors?: Record<string, string>;
    compact?: boolean;
    /** Varian: semua gambar dalam satu galeri tanpa slot utama terpisah */
    variantMode?: boolean;
};

const SLOT_LABELS = ['Depan', 'Selanjutnya', 'Selanjutnya', 'Perbesar', 'Detail', 'Ukuran'];

const blobUrlCache = new WeakMap<File, string>();

function previewUrl(item: GalleryItem | { url: string; file?: File | null }) {
    if ('file' in item && item.file) {
        const cached = blobUrlCache.get(item.file);
        if (cached) {
            return cached;
        }

        const url = URL.createObjectURL(item.file);
        blobUrlCache.set(item.file, url);

        return url;
    }

    return item.url;
}

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
    compact = false,
    variantMode = false,
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const hasMainPreview = mainImageFile ? true : removeMainImage ? false : !!mainImageUrl;
    const mainPreview = mainImageFile
        ? URL.createObjectURL(mainImageFile)
        : removeMainImage
          ? null
          : mainImageUrl;

    const handleFilesSelected = useCallback(
        (fileList: FileList | null) => {
            if (!fileList?.length) {
                return;
            }

            const fileArray = Array.from(fileList);
            // #region agent log
            fetch('http://127.0.0.1:7792/ingest/c8298905-a0de-43df-a1c3-eaa382f54638',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'227592'},body:JSON.stringify({sessionId:'227592',runId:'post-fix',hypothesisId:'H2-H3',location:'ProductGalleryField.tsx:handleFilesSelected',message:'files snapshotted',data:{variantMode,snapshotCount:fileArray.length,galleryCount:gallery.length},timestamp:Date.now()})}).catch(()=>{});
            // #endregion

            if (variantMode) {
                onAddGallery(fileArray);
                return;
            }

            if (!hasMainPreview) {
                onMainImageChange(fileArray[0]);
                if (fileArray.length > 1) {
                    onAddGallery(fileArray.slice(1));
                }
                return;
            }

            onAddGallery(fileArray);
        },
        [gallery.length, hasMainPreview, onAddGallery, onMainImageChange, variantMode],
    );

    const slotSize = compact ? 'h-20 w-20' : 'h-28 w-28';
    const allItems = variantMode
        ? gallery
        : [
              ...(mainPreview
                  ? [{ path: '__main__', url: mainPreview, file: mainImageFile ?? undefined }]
                  : []),
              ...gallery,
          ];

    const handleRemove = (path: string) => {
        if (path === '__main__') {
            onMainImageChange(null);
            onRemoveMainImage(true);
            return;
        }

        onRemoveGallery(path);
    };

    return (
        <div className="space-y-3">
            {!compact && (
                <p className="text-sm text-muted-foreground">
                    Resolusi gambar sebaiknya 560px × 609px. Format: png, jpeg, jpg, webp.
                </p>
            )}

            <div>
                {!variantMode && <Label>Gambar</Label>}
                <div className={cn('mt-2 flex flex-wrap gap-3', compact && 'mt-0')}>
                    {allItems.map((item, index) => (
                        <div key={`${item.path}-${index}`} className="relative shrink-0">
                            <div
                                className={cn(
                                    'relative overflow-hidden rounded-md border bg-muted',
                                    slotSize,
                                )}
                            >
                                <img
                                    src={previewUrl(item)}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="destructive"
                                    className="absolute right-1 top-1 size-6"
                                    onClick={() => handleRemove(item.path)}
                                >
                                    <X className="size-3" />
                                </Button>
                            </div>
                            {!compact && (
                                <p className="mt-1 text-center text-xs text-muted-foreground">
                                    {SLOT_LABELS[index] ?? `Gambar ${index + 1}`}
                                </p>
                            )}
                        </div>
                    ))}

                    <button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                        className={cn(
                            'flex shrink-0 flex-col items-center justify-center rounded-md border border-dashed bg-muted/30 text-muted-foreground transition hover:bg-muted/60',
                            slotSize,
                        )}
                    >
                        <ImagePlus className={compact ? 'size-5' : 'size-6'} />
                        <span className={cn('mt-1 px-1 text-center', compact ? 'text-[10px]' : 'text-xs')}>
                            Tambah Gambar
                        </span>
                    </button>
                </div>

                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    multiple
                    className="hidden"
                    onChange={(e) => {
                        const input = e.target;
                        handleFilesSelected(input.files);
                        input.value = '';
                    }}
                />
            </div>

            {!variantMode && (
                <>
                    <FieldError message={errors.image} />
                    <FieldError message={errors.new_images} />
                </>
            )}
            {variantMode && <FieldError message={errors.new_images} />}
        </div>
    );
}
