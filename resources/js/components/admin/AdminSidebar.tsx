import { Link, useForm, usePage } from '@inertiajs/react';
import { ChevronsUpDown, ExternalLink, LogOut } from 'lucide-react';
import { groupNavItems, isNavItemActive } from '@/lib/admin-nav';
import type { SharedPageProps } from '@/types';
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
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
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

export function AdminSidebar() {
    const { auth, theme } = usePage<SharedPageProps>().props;
    const { url } = usePage();
    const admin = auth.admin!;
    const { post } = useForm({});
    const navGroups = groupNavItems(admin.permissions, admin.isSuperAdmin);

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin">
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground font-serif font-bold text-sm">
                                    {theme.brandName.charAt(0)}
                                </div>
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
                {navGroups.map((group, groupIndex) => (
                    <SidebarGroup key={group.section ?? `main-${groupIndex}`}>
                        {group.section && <SidebarGroupLabel>{group.section}</SidebarGroupLabel>}
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {group.items.map((item) => {
                                    const Icon = item.icon;
                                    const isActive = isNavItemActive(url, item);

                                    return (
                                        <SidebarMenuItem key={item.href}>
                                            <SidebarMenuButton asChild isActive={isActive} tooltip={item.label}>
                                                <Link href={item.href}>
                                                    <Icon />
                                                    <span>{item.label}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                ))}
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
