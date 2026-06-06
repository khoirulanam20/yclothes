import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';

type Promotion = {
    type: 'cart' | 'catalog';
    name: string;
    description?: string | null;
    metaTitle: string;
    metaDescription?: string | null;
    bannerImageUrl?: string | null;
    couponCode?: string | null;
    discountType?: string;
    discountAmount?: number;
    ruleType?: string;
};

type Props = { promotion: Promotion };

export default function Show({ promotion }: Props) {
    return (
        <GuestLayout>
            <Head title={promotion.metaTitle}>
                {promotion.metaDescription && (
                    <meta name="description" content={promotion.metaDescription} />
                )}
            </Head>
            <PageContainer className="py-8">
                {promotion.bannerImageUrl && (
                    <img
                        src={promotion.bannerImageUrl}
                        alt={promotion.name}
                        className="w-full max-h-80 object-cover rounded-2xl mb-6"
                    />
                )}
                <h1 className="text-3xl font-semibold">{promotion.name}</h1>
                {promotion.description && (
                    <p className="text-muted-foreground mt-3 whitespace-pre-line">{promotion.description}</p>
                )}
                <div className="mt-6 flex flex-wrap gap-3">
                    {promotion.type === 'cart' && promotion.couponCode && (
                        <div className="rounded-lg border px-4 py-2 bg-muted/50">
                            <span className="text-sm text-muted-foreground">Kode Kupon: </span>
                            <strong>{promotion.couponCode}</strong>
                        </div>
                    )}
                    {promotion.discountAmount != null && promotion.discountAmount > 0 && (
                        <div className="rounded-lg border px-4 py-2">
                            Diskon: {promotion.discountType === 'percentage'
                                ? `${promotion.discountAmount}%`
                                : formatRupiah(promotion.discountAmount)}
                        </div>
                    )}
                </div>
                <Button asChild className="mt-8">
                    <Link href="/products">Belanja Sekarang</Link>
                </Button>
            </PageContainer>
        </GuestLayout>
    );
}
