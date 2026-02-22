import { usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    const { company } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {company?.logo_url ? (
                    <img
                        src={company.logo_url}
                        alt={company.name ?? 'Logo'}
                        className="size-8 rounded-md object-cover"
                    />
                ) : (
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate font-semibold leading-tight">
                    {company?.name ?? 'Laravel Starter Kit'}
                </span>
            </div>
        </>
    );
}
