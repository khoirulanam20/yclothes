/** Normalisasi path gambar/storage agar konsisten di halaman guest. */
export function normalizeCmsHtml(html: string): string {
    if (!html) {
        return html;
    }

    return html
        .replace(/(?:\.\.\/)+storage\/([^"'>\s]+)/gi, '/storage/$1')
        .replace(/(?<=["'])storage\/([^"'>\s]+)/gi, '/storage/$1');
}
