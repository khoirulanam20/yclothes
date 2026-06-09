import { Link, useForm, usePage } from '@inertiajs/react';
import { LogOut, MapPin, MessageCircle } from 'lucide-react';
import { MobileBottomSheet } from '@/components/storefront/MobileBottomSheet';
import { Button } from '@/components/ui/button';
import { getDrawerNavItems } from '@/lib/mobile-nav';
import { resolveNav } from '@/lib/storefront-nav';
import type { SharedPageProps } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function MobileMenuDrawer({ open, onOpenChange }: Props) {
    const { theme, navigation, categories, auth } = usePage<SharedPageProps>().props;
    const { post } = useForm();
    const headerNav = resolveNav(navigation.header, navigation.footer);
    const footerNav = resolveNav(navigation.footer, navigation.footer);
    const drawerItems = getDrawerNavItems(headerNav, footerNav);
    const rootCategories = categories.filter((cat) => cat.parentId == null);
    const customer = auth.customer;

    const close = () => onOpenChange(false);

    return (
        <MobileBottomSheet open={open} onOpenChange={onOpenChange} title={theme.brandName} contentClassName="p-4">
            <div className="flex flex-col gap-6 pb-6">
                {drawerItems.length > 0 && (
                    <section>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Navigasi
                        </p>
                        <ul className="space-y-1">
                            {drawerItems.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={item.url}
                                        onClick={close}
                                        className="flex min-h-11 items-center rounded-lg px-3 py-2.5 text-sm text-foreground transition-colors hover:bg-muted hover:text-primary"
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {rootCategories.length > 0 && (
                    <section>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Kategori
                        </p>
                        <ul className="space-y-1">
                            <li>
                                <Link
                                    href="/products"
                                    onClick={close}
                                    className="flex min-h-11 items-center rounded-lg px-3 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted hover:text-primary"
                                >
                                    Semua Produk
                                </Link>
                            </li>
                            {rootCategories.map((cat) => (
                                <li key={cat.id}>
                                    <Link
                                        href={`/products?category=${cat.slug}`}
                                        onClick={close}
                                        className="flex min-h-11 items-center rounded-lg px-3 py-2.5 text-sm text-foreground transition-colors hover:bg-muted hover:text-primary"
                                    >
                                        {cat.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section>
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Bantuan
                    </p>
                    <ul className="space-y-1">
                        {theme.storeLocation && (
                            <li className="flex gap-2 rounded-lg px-3 py-2.5 text-sm text-muted-foreground">
                                <MapPin className="mt-0.5 size-4 shrink-0" />
                                <span>{theme.storeLocation}</span>
                            </li>
                        )}
                        {theme.waNumber && (
                            <li>
                                <a
                                    href={`https://wa.me/${theme.waNumber}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    onClick={close}
                                    className="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-primary transition-colors hover:bg-primary/10"
                                >
                                    <MessageCircle className="size-4 shrink-0" />
                                    Chat WhatsApp
                                </a>
                            </li>
                        )}
                    </ul>
                </section>

                <section className="border-t pt-4">
                    {customer ? (
                        <ul className="space-y-1">
                            <li>
                                <Link
                                    href="/account/orders"
                                    onClick={close}
                                    className="flex min-h-11 items-center rounded-lg px-3 py-2.5 text-sm transition-colors hover:bg-muted hover:text-primary"
                                >
                                    Pesanan Saya
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/account/wishlist"
                                    onClick={close}
                                    className="flex min-h-11 items-center rounded-lg px-3 py-2.5 text-sm transition-colors hover:bg-muted hover:text-primary"
                                >
                                    Wishlist
                                </Link>
                            </li>
                            <li>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="h-11 w-full justify-start px-3 text-sm font-normal text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    onClick={() => {
                                        close();
                                        post('/account/logout');
                                    }}
                                >
                                    <LogOut className="mr-2 size-4 shrink-0" />
                                    Keluar
                                </Button>
                            </li>
                        </ul>
                    ) : (
                        <div className="flex flex-col gap-2">
                            <Button asChild className="w-full">
                                <Link href="/account/login" onClick={close}>
                                    Masuk
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full">
                                <Link href="/account/register" onClick={close}>
                                    Daftar
                                </Link>
                            </Button>
                        </div>
                    )}
                </section>
            </div>
        </MobileBottomSheet>
    );
}
