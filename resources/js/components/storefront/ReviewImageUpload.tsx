import { ImagePlus, Loader2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { compressImages, MAX_IMAGE_FILE_SIZE, formatFileSize } from '@/lib/compressImage';
import { guestToast } from '@/lib/guestToast';
import { cn } from '@/lib/utils';

const MAX_IMAGES = 5;

type Props = {
    files: File[];
    onChange: (files: File[]) => void;
    className?: string;
};

function fileKey(file: File): string {
    return `${file.name}-${file.size}-${file.lastModified}`;
}

function dedupeFiles(files: File[]): File[] {
    const seen = new Set<string>();
    return files.filter((file) => {
        const key = fileKey(file);
        if (seen.has(key)) {
            return false;
        }
        seen.add(key);
        return true;
    });
}

export function ReviewImageUpload({ files, onChange, className }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const processingRef = useRef(false);
    const [processing, setProcessing] = useState(false);
    const [previewUrls, setPreviewUrls] = useState<string[]>([]);

    useEffect(() => {
        const urls = files.map((file) => URL.createObjectURL(file));
        setPreviewUrls(urls);

        return () => {
            urls.forEach((url) => URL.revokeObjectURL(url));
        };
    }, [files]);

    const addFiles = async (incoming: FileList | null) => {
        if (!incoming?.length || processingRef.current) {
            return;
        }

        processingRef.current = true;
        setProcessing(true);

        try {
            const incomingFiles = Array.from(incoming);
            const compressed = await compressImages(incomingFiles, (message) => guestToast.error(message));

            if (compressed.length === 0) {
                return;
            }

            const merged = dedupeFiles([...files, ...compressed]).slice(0, MAX_IMAGES);
            onChange(merged);
        } finally {
            processingRef.current = false;
            setProcessing(false);
        }
    };

    const removeFile = (index: number) => {
        onChange(files.filter((_, i) => i !== index));
    };

    return (
        <div className={cn('space-y-2', className)}>
            <p className="text-xs font-medium text-muted-foreground">
                Foto produk (opsional, maks. {MAX_IMAGES}, per foto maks. {formatFileSize(MAX_IMAGE_FILE_SIZE)})
            </p>
            <div className="flex flex-wrap gap-2">
                {files.map((file, index) => (
                    <div key={fileKey(file)} className="relative">
                        <img
                            src={previewUrls[index]}
                            alt=""
                            className="size-16 rounded-lg border object-cover"
                        />
                        <Button
                            type="button"
                            size="icon"
                            variant="destructive"
                            className="absolute -right-1 -top-1 size-5"
                            onClick={() => removeFile(index)}
                        >
                            <X className="size-3" />
                        </Button>
                    </div>
                ))}
                {files.length < MAX_IMAGES && (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => inputRef.current?.click()}
                        className="flex size-16 flex-col items-center justify-center rounded-lg border border-dashed bg-muted/30 text-muted-foreground transition-colors hover:bg-muted/60 disabled:opacity-50"
                    >
                        {processing ? (
                            <Loader2 className="size-4 animate-spin" />
                        ) : (
                            <>
                                <ImagePlus className="size-4" />
                                <span className="mt-0.5 text-[10px]">Tambah</span>
                            </>
                        )}
                    </button>
                )}
            </div>
            <input
                ref={inputRef}
                type="file"
                accept="image/*"
                multiple
                className="hidden"
                onChange={(e) => {
                    void addFiles(e.target.files);
                    e.target.value = '';
                }}
            />
        </div>
    );
}
