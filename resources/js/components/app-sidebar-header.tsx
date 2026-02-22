import { usePage } from '@inertiajs/react';
import { Bell, ChevronDown, Moon, Search, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { SharedData } from '@/types';

type ColorScheme = 'light' | 'dark';

function useDarkMode(): [ColorScheme, () => void] {
    const [appearance, setAppearance] = useState<ColorScheme>('light');

    useEffect(() => {
        const stored = localStorage.getItem('appearance') as ColorScheme | null;
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initial: ColorScheme = stored ?? (prefersDark ? 'dark' : 'light');
        setAppearance(initial);
        document.documentElement.classList.toggle('dark', initial === 'dark');
    }, []);

    const toggle = () => {
        setAppearance((prev) => {
            const next: ColorScheme = prev === 'dark' ? 'light' : 'dark';
            localStorage.setItem('appearance', next);
            document.documentElement.classList.toggle('dark', next === 'dark');
            return next;
        });
    };

    return [appearance, toggle];
}

export function AppSidebarHeader() {
    const { auth } = usePage<SharedData>().props;
    const [appearance, toggleAppearance] = useDarkMode();

    return (
        <header className="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/50 bg-background px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6">
            {/* Left — sidebar toggle */}
            <SidebarTrigger className="-ml-1 shrink-0" />

            {/* Center — search trigger */}
            <button
                type="button"
                className="flex flex-1 items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-ring md:max-w-xs"
            >
                <Search className="size-4 shrink-0" />
                <span className="hidden truncate sm:inline">Search or type command...</span>
                <kbd className="ml-auto hidden rounded border border-border bg-background px-1.5 py-0.5 font-mono text-xs text-muted-foreground lg:inline">
                    ⌘K
                </kbd>
            </button>

            {/* Right — actions + user */}
            <div className="ml-auto flex items-center gap-1">
                {/* Dark mode toggle */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-9 rounded-full"
                    onClick={toggleAppearance}
                    aria-label="Toggle dark mode"
                >
                    {appearance === 'dark' ? (
                        <Sun className="size-4.5" />
                    ) : (
                        <Moon className="size-4.5" />
                    )}
                </Button>

                {/* Notification bell */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative size-9 rounded-full"
                    aria-label="Notifications"
                >
                    <Bell className="size-4.5" />
                    <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-primary" />
                </Button>

                {/* User dropdown */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className="ml-1 flex h-9 items-center gap-2 rounded-full pl-1 pr-2 hover:bg-accent"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronDown className="size-3.5 text-muted-foreground" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56" align="end">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
