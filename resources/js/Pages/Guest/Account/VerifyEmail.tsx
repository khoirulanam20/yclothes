import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthCard } from '@/components/storefront/AuthCard';
import { Button } from '@/components/ui/button';

export default function VerifyEmail() {
    const { post, processing } = useForm({});

    return (
        <GuestLayout>
            <Head title="Verifikasi Email" />
            <AuthCard title="Verifikasi Email">
                <div className="text-center space-y-4">
                    <p className="text-muted-foreground text-sm">
                        Silakan periksa email Anda untuk link verifikasi. Jika belum menerima, klik tombol di bawah.
                    </p>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/account/email/verification-notification');
                        }}
                    >
                        <Button type="submit" disabled={processing} className="w-full">
                            Kirim Ulang Email Verifikasi
                        </Button>
                    </form>
                    <p className="text-sm">
                        <Link href="/account/profile" className="text-primary hover:underline">
                            Kembali ke profil
                        </Link>
                    </p>
                </div>
            </AuthCard>
        </GuestLayout>
    );
}
