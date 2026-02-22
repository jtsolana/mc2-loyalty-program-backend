import type { Auth } from '@/types/auth';
import type { CompanyProfile } from '@/types/admin';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            company: CompanyProfile | null;
            [key: string]: unknown;
        };
    }
}
