import { Link } from '@inertiajs/react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Fragment } from 'react';

export type AdminBreadcrumbItem = {
    label: string;
    href?: string;
};

export function AdminBreadcrumb({ items = [] }: { items?: AdminBreadcrumbItem[] }) {
    const crumbs: AdminBreadcrumbItem[] = [{ label: 'Admin', href: '/admin' }, ...items];

    return (
        <Breadcrumb data-tour="breadcrumb">
            <BreadcrumbList>
                {crumbs.map((item, index) => {
                    const isLast = index === crumbs.length - 1;

                    return (
                        <Fragment key={`${item.label}-${index}`}>
                            {index > 0 && <BreadcrumbSeparator />}
                            <BreadcrumbItem>
                                {isLast || !item.href ? (
                                    <BreadcrumbPage>{item.label}</BreadcrumbPage>
                                ) : (
                                    <BreadcrumbLink asChild>
                                        <Link href={item.href}>{item.label}</Link>
                                    </BreadcrumbLink>
                                )}
                            </BreadcrumbItem>
                        </Fragment>
                    );
                })}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
