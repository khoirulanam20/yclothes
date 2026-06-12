import { Link, useForm, usePage } from '@inertiajs/react';
import { ChevronsUpDown, ChevronDown, ExternalLink, LogOut } from 'lucide-react';
import { useEffect, useState } from 'react';
import { hrefToTourKey } from '@/lib/admin-tour-keys';
import {
    formatBadgeCount,
    groupNavItems,
    isNavGroupActive,
    isNavItemActive,
    type AdminBadgeKey,
    type AdminNavGroup,
    type AdminNavItem,
} from '@/lib/admin-nav';
import type { AdminBadges, SharedPageProps } from '@/types';
import { cn } from '@/lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarRail,
} from '@/components/ui/sidebar';

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function getBadgeCount(badges: AdminBadges | null | undefined, key?: AdminBadgeKey): number {
    if (!key || !badges) return 0;
    return badges[key] ?? 0;
}

function getGroupBadgeCount(group: AdminNavGroup, badges: AdminBadges | null | undefined): number {
    return group.items.reduce((sum, item) => sum + getBadgeCount(badges, item.badgeKey), 0);
}

function NavBadge({ count }: { count: number }) {
    if (count <= 0) return null;

    return (
        <span className="ml-auto shrink-0 rounded-md bg-primary px-1.5 py-0.5 text-[10px] font-medium text-primary-foreground tabular-nums">
            {formatBadgeCount(count)}
        </span>
    );
}

function NavGroupSection({
    group,
    url,
    open,
    onToggle,
    badges,
}: {
    group: AdminNavGroup;
    url: string;
    open: boolean;
    onToggle: () => void;
    badges?: AdminBadges | null;
}) {
    const GroupIcon = group.icon;
    const groupActive = isNavGroupActive(url, group);
    const groupBadgeCount = getGroupBadgeCount(group, badges);

    if (!group.collapsible) {
        const item = group.items[0];
        const ItemIcon = item.icon;
        const count = getBadgeCount(badges, item.badgeKey);

        const tourKey = hrefToTourKey(item.href);

        return (
            <SidebarMenuItem>
                <SidebarMenuButton asChild isActive={isNavItemActive(url, item)} tooltip={item.label}>
                    <Link href={item.href} {...(tourKey ? { 'data-tour': `nav-${tourKey}` } : {})}>
                        <ItemIcon />
                        <span>{item.label}</span>
                    </Link>
                </SidebarMenuButton>
                {count > 0 && (
                    <SidebarMenuBadge className="bg-primary text-primary-foreground">
                        {formatBadgeCount(count)}
                    </SidebarMenuBadge>
                )}
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                tooltip={group.label}
                isActive={groupActive}
                onClick={onToggle}
            >
                <GroupIcon />
                <span className="flex-1 truncate">{group.label}</span>
                {groupBadgeCount > 0 && (
                    <span className="mr-1 shrink-0 rounded-md bg-primary px-1.5 py-0.5 text-[10px] font-medium text-primary-foreground tabular-nums group-data-[collapsible=icon]:hidden">
                        {formatBadgeCount(groupBadgeCount)}
                    </span>
                )}
                <ChevronDown
                    className={cn(
                        'size-4 shrink-0 transition-transform group-data-[collapsible=icon]:hidden',
                        open && 'rotate-180',
                    )}
                />
            </SidebarMenuButton>
            {open && (
                <SidebarMenuSub>
                    {group.items.map((item) => (
                        <NavSubItem key={item.href} item={item} url={url} badges={badges} />
                    ))}
                </SidebarMenuSub>
            )}
        </SidebarMenuItem>
    );
}

function NavSubItem({
    item,
    url,
    badges,
}: {
    item: AdminNavItem;
    url: string;
    badges?: AdminBadges | null;
}) {
    const count = getBadgeCount(badges, item.badgeKey);
    const tourKey = hrefToTourKey(item.href);

    return (
        <SidebarMenuSubItem>
            <SidebarMenuSubButton asChild isActive={isNavItemActive(url, item)}>
                <Link
                    href={item.href}
                    className="flex items-center justify-between gap-2"
                    {...(tourKey ? { 'data-tour': `nav-${tourKey}` } : {})}
                >
                    <span>{item.label}</span>
                    <NavBadge count={count} />
                </Link>
            </SidebarMenuSubButton>
        </SidebarMenuSubItem>
    );
}

function BrandMark({ brandName, brandLogo }: { brandName: string; brandLogo: string | null }) {
    if (brandLogo) {
        return (
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-lg bg-background">
                <img src={brandLogo} alt={brandName} className="size-full object-contain" />
            </div>
        );
    }

    return (
        <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground font-serif font-bold text-sm">
            {brandName.charAt(0)}
        </div>
    );
}

export function AdminSidebar() {
    const { auth, theme, adminBadges } = usePage<SharedPageProps>().props;
    const { url } = usePage();
    const admin = auth.admin!;
    const { post } = useForm({});
    const navGroups = groupNavItems(admin.permissions, admin.isSuperAdmin);

    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() =>
        Object.fromEntries(
            navGroups
                .filter((group) => group.collapsible)
                .map((group) => [group.label, isNavGroupActive(url, group)]),
        ),
    );

    useEffect(() => {
        setOpenGroups((current) => {
            const next = { ...current };
            let changed = false;

            navGroups.forEach((group) => {
                if (group.collapsible && isNavGroupActive(url, group) && !next[group.label]) {
                    next[group.label] = true;
                    changed = true;
                }
            });

            return changed ? next : current;
        });
    }, [url]);

    const toggleGroup = (label: string) => {
        setOpenGroups((current) => ({
            ...current,
            [label]: !current[label],
        }));
    };

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin">
                                <BrandMark brandName={theme.brandName} brandLogo={theme.brandLogo} />
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-serif font-bold">{theme.brandName}</span>
                                    <span className="truncate text-xs text-muted-foreground">Admin Panel</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {navGroups.map((group) => (
                                <NavGroupSection
                                    key={group.label}
                                    group={group}
                                    url={url}
                                    open={openGroups[group.label] ?? false}
                                    onToggle={() => toggleGroup(group.label)}
                                    badges={adminBadges}
                                />
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton
                                    size="lg"
                                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                >
                                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-muted text-sm font-medium">
                                        {getInitials(admin.name)}
                                    </div>
                                    <div className="grid flex-1 text-left text-sm leading-tight">
                                        <span className="truncate font-medium">{admin.name}</span>
                                        <span className="truncate text-xs text-muted-foreground">{admin.email}</span>
                                    </div>
                                    <ChevronsUpDown className="ml-auto size-4" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                                side="bottom"
                                align="end"
                                sideOffset={4}
                            >
                                <DropdownMenuItem asChild>
                                    <a href="/" target="_blank" rel="noopener noreferrer">
                                        <ExternalLink className="size-4" />
                                        Lihat Toko
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    onClick={() => post('/admin/logout')}
                                >
                                    <LogOut className="size-4" />
                                    Keluar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
