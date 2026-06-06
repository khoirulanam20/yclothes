import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Check,
    Copy,
    ExternalLink,
    Eye,
    FolderPlus,
    List,
    PanelsTopLeft,
    Pencil,
    Trash2,
    X,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type ActionVariant = 'default' | 'destructive';

type AdminTableActionProps = {
    label: string;
    icon: LucideIcon;
    href?: string;
    onClick?: () => void;
    variant?: ActionVariant;
    target?: string;
    rel?: string;
};

export function AdminTableActions({ children }: { children: ReactNode }) {
    return <div className="flex items-center justify-end gap-0.5">{children}</div>;
}

export function AdminTableAction({
    label,
    icon: Icon,
    href,
    onClick,
    variant = 'default',
    target,
    rel,
}: AdminTableActionProps) {
    const destructive = variant === 'destructive';

    const buttonClass = cn(
        'size-8',
        destructive && 'text-destructive hover:bg-destructive/10 hover:text-destructive',
    );

    const content = (
        <>
            <Icon className="size-4" />
            <span className="sr-only">{label}</span>
        </>
    );

    const trigger = href ? (
        <Button variant="ghost" size="icon" className={buttonClass} asChild>
            <Link href={href} target={target} rel={rel}>
                {content}
            </Link>
        </Button>
    ) : (
        <Button type="button" variant="ghost" size="icon" className={buttonClass} onClick={onClick}>
            {content}
        </Button>
    );

    return (
        <Tooltip>
            <TooltipTrigger asChild>{trigger}</TooltipTrigger>
            <TooltipContent side="top">{label}</TooltipContent>
        </Tooltip>
    );
}

export function AdminEditAction({ href }: { href: string }) {
    return <AdminTableAction label="Edit" icon={Pencil} href={href} />;
}

export function AdminViewAction({ href }: { href: string }) {
    return <AdminTableAction label="Detail" icon={Eye} href={href} />;
}

export function AdminDeleteAction({ onClick }: { onClick: () => void }) {
    return <AdminTableAction label="Hapus" icon={Trash2} variant="destructive" onClick={onClick} />;
}

export function AdminDuplicateAction({ onClick }: { onClick: () => void }) {
    return <AdminTableAction label="Duplikat" icon={Copy} onClick={onClick} />;
}

export function AdminApproveAction({ onClick }: { onClick: () => void }) {
    return <AdminTableAction label="Setujui" icon={Check} onClick={onClick} />;
}

export function AdminRejectAction({ onClick }: { onClick: () => void }) {
    return <AdminTableAction label="Tolak" icon={X} variant="destructive" onClick={onClick} />;
}

export function AdminBuilderAction({ href }: { href: string }) {
    return <AdminTableAction label="Builder" icon={PanelsTopLeft} href={href} />;
}

export function AdminPreviewAction({ href }: { href: string }) {
    return (
        <AdminTableAction
            label="Preview"
            icon={ExternalLink}
            href={href}
            target="_blank"
            rel="noopener noreferrer"
        />
    );
}

export function AdminSubAction({ href }: { href: string }) {
    return <AdminTableAction label="Sub kategori" icon={FolderPlus} href={href} />;
}

export function AdminItemsAction({ href }: { href: string }) {
    return <AdminTableAction label="Items" icon={List} href={href} />;
}
