import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { cn } from '@/lib/utils';

const links = [
    { href: '/account/profile', label: 'Profil' },
    { href: '/account/orders', label: 'Pesanan Saya' },
    { href: '/account/addresses', label: 'Alamat' },
    { href: '/account/wishlist', label: 'Wishlist' },
];

export default function AccountLayout({ children, title }: PropsWithChildren<{ title?: string }>) {
    const { url } = usePage();

    return (
        <GuestLayout>
            <PageContainer>
                {title && <h1 className="text-xl font-bold mb-4">{title}</h1>}
                <div className="flex flex-col md:flex-row gap-4">
                    <aside className="md:w-52 shrink-0">
                        <SectionCard noPadding>
                            <nav className="flex md:flex-col p-2 gap-1 overflow-x-auto">
                                {links.map((link) => {
                                    const active = url.startsWith(link.href);
                                    return (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            className={cn(
                                                'px-3 py-2 rounded-md text-sm whitespace-nowrap transition-colors',
                                                active
                                                    ? 'bg-primary/10 text-primary font-medium'
                                                    : 'hover:bg-muted text-muted-foreground',
                                            )}
                                        >
                                            {link.label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </SectionCard>
                    </aside>
                    <div className="flex-1 min-w-0">{children}</div>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
