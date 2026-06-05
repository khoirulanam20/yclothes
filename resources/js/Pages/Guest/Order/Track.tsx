import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FieldError } from '@/components/admin/FieldError';

export default function Track() {
    const { data, setData, post, processing, errors } = useForm({
        order_number: '',
        email: '',
    });

    return (
        <GuestLayout>
            <Head title="Lacak Pesanan" />
            <PageContainer narrow>
                <SectionCard title="Lacak Pesanan">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/order/track');
                        }}
                        className="space-y-4"
                    >
                        <div>
                            <Label htmlFor="order_number">Nomor Pesanan</Label>
                            <Input
                                id="order_number"
                                value={data.order_number}
                                onChange={(e) => setData('order_number', e.target.value)}
                                required
                            />
                            <FieldError message={errors.order_number} />
                        </div>
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            <FieldError message={errors.email} />
                        </div>
                        <Button type="submit" className="w-full" disabled={processing}>
                            Cari Pesanan
                        </Button>
                    </form>
                </SectionCard>
            </PageContainer>
        </GuestLayout>
    );
}
