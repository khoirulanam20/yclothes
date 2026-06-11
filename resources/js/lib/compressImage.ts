import imageCompression from 'browser-image-compression';

export const MAX_IMAGE_FILE_SIZE = 2 * 1024 * 1024; // 2 MB — selaras validasi backend

export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function supportsWebp(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    const canvas = document.createElement('canvas');
    return canvas.toDataURL('image/webp').startsWith('data:image/webp');
}

export type CompressImageResult =
    | { ok: true; file: File }
    | { ok: false; reason: 'too_large'; size: number };

export async function compressImage(file: File): Promise<CompressImageResult> {
    if (!file.type.startsWith('image/')) {
        return { ok: true, file };
    }

    if (file.size <= MAX_IMAGE_FILE_SIZE) {
        return { ok: true, file };
    }

    const useWebp = supportsWebp();

    try {
        const compressed = await imageCompression(file, {
            maxSizeMB: 1.9,
            maxWidthOrHeight: 1920,
            useWebWorker: true,
            fileType: useWebp ? 'image/webp' : 'image/jpeg',
        });

        const ext = useWebp ? 'webp' : 'jpg';
        const baseName = file.name.replace(/\.[^.]+$/, '') || 'image';
        const output =
            compressed.name.endsWith(`.${ext}`)
                ? compressed
                : new File([compressed], `${baseName}.${ext}`, {
                      type: useWebp ? 'image/webp' : 'image/jpeg',
                      lastModified: Date.now(),
                  });

        if (output.size <= MAX_IMAGE_FILE_SIZE) {
            return { ok: true, file: output };
        }

        return { ok: false, reason: 'too_large', size: output.size };
    } catch {
        if (file.size <= MAX_IMAGE_FILE_SIZE) {
            return { ok: true, file };
        }

        return { ok: false, reason: 'too_large', size: file.size };
    }
}

export async function compressImages(
    files: File[],
    onError?: (message: string) => void,
): Promise<File[]> {
    const results: File[] = [];

    for (const file of files) {
        const result = await compressImage(file);

        if (result.ok) {
            results.push(result.file);
            continue;
        }

        onError?.(
            `Gambar "${file.name}" terlalu besar (${formatFileSize(result.size)}). Maksimum ${formatFileSize(MAX_IMAGE_FILE_SIZE)}.`,
        );
    }

    return results;
}
