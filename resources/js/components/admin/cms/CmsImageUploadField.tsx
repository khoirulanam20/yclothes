import { ImagePlus, Loader2, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { storageUrl } from '@/cms/storageUrl';
import { uploadCmsImage } from '@/cms/uploadCmsImage';
import { Button } from '@/components/ui/button';

type Props = {
    value: string;
    onChange: (value: string) => void;
};

export function CmsImageUploadField({ value, onChange }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const previewUrl = storageUrl(value);

    const handleFile = async (file: File | undefined) => {
        if (!file) {
            return;
        }

        setUploading(true);
        setError(null);

        try {
            const path = await uploadCmsImage(file);
            onChange(path);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Gagal mengunggah gambar');
        } finally {
            setUploading(false);

            if (inputRef.current) {
                inputRef.current.value = '';
            }
        }
    };

    return (
        <div className="space-y-2">
            {previewUrl ? (
                <img src={previewUrl} alt="" className="h-28 w-full rounded-md border object-cover" />
            ) : (
                <div className="flex h-28 w-full items-center justify-center rounded-md border border-dashed text-xs text-muted-foreground">
                    Belum ada gambar
                </div>
            )}

            <div className="flex gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="flex-1"
                    disabled={uploading}
                    onClick={() => inputRef.current?.click()}
                >
                    {uploading ? (
                        <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                    ) : (
                        <ImagePlus className="mr-1.5 h-4 w-4" />
                    )}
                    {uploading ? 'Mengunggah...' : value ? 'Ganti gambar' : 'Unggah gambar'}
                </Button>

                {value && (
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="shrink-0"
                        disabled={uploading}
                        onClick={() => onChange('')}
                        aria-label="Hapus gambar"
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                )}
            </div>

            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp,image/jpg"
                className="hidden"
                onChange={(e) => handleFile(e.target.files?.[0])}
            />

            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
