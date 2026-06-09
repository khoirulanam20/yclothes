import { Link, usePage } from '@inertiajs/react';
import { Search, ShoppingCart, User } from 'lucide-react';
import { CategoryMenu } from '@/components/storefront/CategoryMenu';
import { Input } from '@/components/ui/input';
import { resolveAccountHref } from '@/lib/mobile-nav';
import type { SharedPageProps } from '@/types';

export function SiteHeader() {
    const { theme, cartCount, auth } = usePage<SharedPageProps>().props;
    const accountHref = resolveAccountHref(Boolean(auth.customer));

    return (
        <header className="sticky top-0 z-40 border-b bg-header-background shadow-sm">
            <div className="container mx-auto flex h-16 items-center gap-4 px-4">
                <Link href="/" className="flex items-center gap-2 font-bold text-lg shrink-0 text-primary">
                    {theme.brandLogo && <img src={theme.brandLogo} alt="" className="h-8 w-auto" />}
                    <span className="hidden sm:inline">{theme.brandName}</span>
                </Link>

                <div className="flex flex-1 items-center min-w-0 h-10 rounded-full border border-border bg-muted/50 overflow-visible">
                    <div className="hidden md:block">
                        <CategoryMenu />
                    </div>
                    <form action="/products" method="get" className="flex flex-1 min-w-0">
                        <div className="relative w-full">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                name="search"
                                placeholder="Cari produk..."
                                className="h-10 border-0 bg-transparent shadow-none rounded-none md:rounded-r-full pl-9 focus-visible:ring-0 focus-visible:ring-offset-0"
                            />
                        </div>
                    </form>
                </div>

                <div className="flex items-center gap-1 shrink-0">
                    <Link
                        href={accountHref}
                        className="flex md:hidden items-center p-2 hover:text-primary"
                        aria-label={auth.customer ? 'Profil akun' : 'Masuk'}
                    >
                        <User className="h-5 w-5" />
                    </Link>
                    {auth.customer ? (
                        <Link
                            href="/account/profile"
                            className="hidden md:flex items-center gap-1.5 text-sm px-2 py-1.5 hover:text-primary"
                        >
                            <User className="h-4 w-4" />
                            <span className="max-w-[80px] truncate">{auth.customer.name}</span>
                        </Link>
                    ) : (
                        <Link
                            href="/account/login"
                            className="hidden md:flex items-center gap-1.5 text-sm px-2 py-1.5 hover:text-primary"
                        >
                            <User className="h-4 w-4" />
                            Masuk
                        </Link>
                    )}
                    <Link href="/cart" className="relative hidden md:block p-2 hover:text-primary">
                        <ShoppingCart className="h-5 w-5" />
                        {cartCount > 0 && (
                            <span className="absolute -top-0.5 -right-0.5 bg-primary text-primary-foreground text-[10px] font-bold rounded-full h-4 min-w-4 px-0.5 flex items-center justify-center">
                                {cartCount > 99 ? '99+' : cartCount}
                            </span>
                        )}
                    </Link>
                </div>
            </div>
        </header>
    );
}
