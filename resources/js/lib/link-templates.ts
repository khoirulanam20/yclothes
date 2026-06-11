export type LinkTemplateOption = {
    id: string;
    label: string;
    url: string;
};

export type LinkTemplateGroup = {
    label: string;
    options: LinkTemplateOption[];
};

let cachedGroups: LinkTemplateGroup[] | null = null;
let inflightRequest: Promise<LinkTemplateGroup[]> | null = null;

function normalizeLinkUrl(url: string): string {
    if (!url) {
        return '';
    }

    const [path, query = ''] = url.split('?');
    if (!query) {
        return path;
    }

    const params = new URLSearchParams(query);
    const sorted = [...params.entries()].sort(([left], [right]) => left.localeCompare(right));
    const normalized = new URLSearchParams();

    for (const [key, value] of sorted) {
        normalized.set(key, value);
    }

    const serialized = normalized.toString();

    return serialized ? `${path}?${serialized}` : path;
}

export async function fetchLinkTemplateGroups(): Promise<LinkTemplateGroup[]> {
    if (cachedGroups) {
        return cachedGroups;
    }

    if (inflightRequest) {
        return inflightRequest;
    }

    inflightRequest = (async () => {
        const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        const token = match ? decodeURIComponent(match[1]) : '';

        const response = await fetch('/admin/link-templates', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': token,
            },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat template link.');
        }

        const data = await response.json() as { groups: LinkTemplateGroup[] };
        cachedGroups = data.groups;

        return cachedGroups;
    })().finally(() => {
        inflightRequest = null;
    });

    return inflightRequest;
}

export function findTemplateIdForUrl(groups: LinkTemplateGroup[], url: string): string {
    const normalizedUrl = normalizeLinkUrl(url);

    for (const group of groups) {
        for (const option of group.options) {
            if (normalizeLinkUrl(option.url) === normalizedUrl) {
                return option.id;
            }
        }
    }

    return '';
}
