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
                                <div className="hidden md:block border-t my-2" />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="justify-start px-3 py-2 h-auto text-sm font-normal text-muted-foreground hover:text-destructive hover:bg-destructive/10 whitespace-nowrap"
                                    onClick={() => post('/account/logout')}
                                >
                                    <LogOut className="h-4 w-4 mr-2 shrink-0" />
                                    Keluar
                                </Button>
                            </nav>
                        </SectionCard>
                    </aside>
                    <div className="flex-1 min-w-0">{children}</div>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
