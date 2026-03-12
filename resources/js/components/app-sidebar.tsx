import { Link, usePage } from '@inertiajs/react';
import { BarChart3, Building2, Gift, Megaphone, Star, Users, Users2 } from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';
import admin from '@/routes/admin';

const RESTRICTED_NAV_ITEMS = new Set(['Users', 'Point Rules', 'Reward Rules', 'Company Profile']);

const allAdminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: BarChart3,
    },
    {
        title: 'Customers',
        href: admin.customers.index(),
        icon: Users,
    },
    {
        title: 'Users',
        href: admin.users.index(),
        icon: Users2,
    },
    {
        title: 'Point Rules',
        href: admin.pointRules.index(),
        icon: Star,
    },
    {
        title: 'Reward Rules',
        href: admin.rewardRules.index(),
        icon: Gift,
    },
    {
        title: 'Promotions',
        href: admin.promotions.index(),
        icon: Megaphone,
    },
    {
        title: 'Company Profile',
        href: admin.companyProfile.edit(),
        icon: Building2,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const canManageRoles = auth.can.roles_manage;
    const adminNavItems = canManageRoles
        ? allAdminNavItems
        : allAdminNavItems.filter((item) => !RESTRICTED_NAV_ITEMS.has(item.title));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={admin.dashboard().url} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
