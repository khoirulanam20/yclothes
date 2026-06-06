import { getCsrfToken } from '@/lib/csrf';

type UploadResponse = {
    path: string;
    url: string;
    message?: string;
    errors?: Record<string, string[]>;
};

export async function uploadEditorImage(file: File): Promise<{ path: string; url: string }> {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch('/admin/editor/upload-image', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
    });

    const data = (await response.json()) as UploadResponse;

    if (!response.ok) {
        throw new Error(data.errors?.image?.[0] ?? data.message ?? 'Gagal mengunggah gambar');
    }

    return { path: data.path, url: data.url };
}

/** @deprecated Use uploadEditorImage */
export async function uploadCmsImage(file: File): Promise<string> {
    const result = await uploadEditorImage(file);

    return result.path;
}
