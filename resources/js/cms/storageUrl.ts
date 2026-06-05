export function storageUrl(path: string | undefined | null): string {
    if (!path) {
        return '';
    }

    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
        return path;
    }

    return `/storage/${path.replace(/^\//, '')}`;
}
