import { Link, useForm, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { PropsWithChildren } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const links = [
    { href: '/account/profile', label: 'Profil' },
    { href: '/account/orders', label: 'Pesanan Saya' },
    { href: '/account/returns', label: 'Retur' },
    { href: '/account/addresses', label: 'Alamat' },
    { href: '/account/wishlist', label: 'Wishlist' },
];

export default function AccountLayout({ children, title }: PropsWithChildren<{ title?: string }>) {
    const { url } = usePage();
    const { post } = useForm();

    return (
        <GuestLayout>
            <PageContainer>
                {title && <h1 className="mb-4 text-2xl font-bold">{title}</h1>}
                <div className="flex flex-col gap-4 md:flex-row">
                    <aside className="shrink-0 md:w-56 md:sticky md:top-24 md:self-start">
                        <SectionCard noPadding className="overflow-hidden">
                            <nav className="flex gap-1 overflow-x-auto p-2 md:flex-col">
                                {links.map((link) => {
                                    const active = url.startsWith(link.href);

                                    return (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            className={cn(
                                                'rounded-lg px-3 py-2.5 text-sm whitespace-nowrap transition-colors',
                                                active
                                                    ? 'bg-primary font-medium text-primary-foreground shadow-sm'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                            )}
                                        >
                                            {link.label}
                                        </Link>
                                    );
                                })}
                                <div className="hidden border-t my-1 md:block" />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="h-auto justify-start whitespace-nowrap px-3 py-2.5 text-sm font-normal text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    onClick={() => post('/account/logout')}
                                >
                                    <LogOut className="mr-2 size-4 shrink-0" />
                                    Keluar
                                </Button>
                            </nav>
                        </SectionCard>
                    </aside>
                    <div className="min-w-0 flex-1">{children}</div>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
