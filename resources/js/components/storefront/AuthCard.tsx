import { usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import type { SharedPageProps } from '@/types';

export function AuthCard({ children, title }: PropsWithChildren<{ title: string }>) {
    const { theme } = usePage<SharedPageProps>().props;

    return (
        <div className="container mx-auto px-4 py-12 max-w-md">
            <div className="rounded-lg bg-card border shadow-sm p-6">
                <div className="flex flex-col items-center mb-6">
                    {theme.brandLogo && (
                        <img src={theme.brandLogo} alt="" className="h-10 w-auto mb-3" />
                    )}
                    <h1 className="text-xl font-bold">{title}</h1>
                </div>
                {children}
            </div>
        </div>
    );
}
