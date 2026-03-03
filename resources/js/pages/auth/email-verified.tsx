import { Head } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';
import AuthLayout from '@/layouts/auth-layout';

export default function EmailVerified() {
    return (
        <AuthLayout
            title="Email verified"
            description="Your email address has been successfully verified. You can now log in to the app."
        >
            <Head title="Email verified" />

            <div className="flex flex-col items-center gap-4 text-center">
                <div className="flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
                    <CheckCircle className="size-8 text-green-600 dark:text-green-400" />
                </div>

                <p className="text-sm text-muted-foreground">
                    You may now close this window and return to the app.
                </p>
            </div>
        </AuthLayout>
    );
}
