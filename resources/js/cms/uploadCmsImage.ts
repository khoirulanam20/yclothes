function getCsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

type UploadResponse = {
    path: string;
    url: string;
    message?: string;
    errors?: Record<string, string[]>;
};

export async function uploadCmsImage(file: File): Promise<string> {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch('/admin/cms-pages/upload-image', {
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

    return data.path;
}
