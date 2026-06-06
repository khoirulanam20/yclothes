import type { AdminBreadcrumbItem } from '@/components/admin/AdminBreadcrumb';

export const CONFIGURATION_HREF = '/admin/configuration';

/** Breadcrumb trail dengan prefix Konfigurasi. */
export function configurationBreadcrumbs(...trail: AdminBreadcrumbItem[]): AdminBreadcrumbItem[] {
    if (trail.length === 0) {
        return [{ label: 'Konfigurasi' }];
    }

    return [{ label: 'Konfigurasi', href: CONFIGURATION_HREF }, ...trail];
}

/** Breadcrumb untuk halaman section Konfigurasi (index CRUD atau form schema). */
export function configurationSectionBreadcrumbs(
    sectionLabel: string,
    sectionHref?: string,
    ...rest: AdminBreadcrumbItem[]
): AdminBreadcrumbItem[] {
    const section: AdminBreadcrumbItem = sectionHref
        ? { label: sectionLabel, href: sectionHref }
        : { label: sectionLabel };

    return configurationBreadcrumbs(section, ...rest);
}
