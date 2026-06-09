import { ReactNode } from 'react';

type Props = {
    title: string;
    action?: ReactNode;
};

export function AccountPageHeader({ title, action }: Props) {
    return (
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
            {action}
        </div>
    );
}
