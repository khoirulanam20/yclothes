import { ImagePlus, X } from 'lucide-react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const MAX_IMAGES = 5;

type Props = {
    files: File[];
    onChange: (files: File[]) => void;
    className?: string;
};

export function ReviewImageUpload({ files, onChange, className }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);

    const addFiles = (incoming: FileList | null) => {
        if (!incoming?.length) {
            return;
        }

        const next = [...files, ...Array.from(incoming)].slice(0, MAX_IMAGES);
        onChange(next);
    };

    const removeFile = (index: number) => {
        onChange(files.filter((_, i) => i !== index));
    };

    return (
        <div className={cn('space-y-2', className)}>
            <p className="text-xs font-medium text-muted-foreground">
                Foto produk (opsional, maks. {MAX_IMAGES})
            </p>
            <div className="flex flex-wrap gap-2">
                {files.map((file, index) => (
                    <div key={`${file.name}-${file.lastModified}-${index}`} className="relative">
                        <img
                            src={URL.createObjectURL(file)}
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
                        onClick={() => inputRef.current?.click()}
                        className="flex size-16 flex-col items-center justify-center rounded-lg border border-dashed bg-muted/30 text-muted-foreground transition-colors hover:bg-muted/60"
                    >
                        <ImagePlus className="size-4" />
                        <span className="mt-0.5 text-[10px]">Tambah</span>
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
                    addFiles(e.target.files);
                    e.target.value = '';
                }}
            />
        </div>
    );
}
