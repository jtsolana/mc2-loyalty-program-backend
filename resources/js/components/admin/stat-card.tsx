import { type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface StatCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: LucideIcon;
    iconClassName?: string;
    trend?: { value: number; label: string };
}

export function StatCard({ title, value, subtitle, icon: Icon, iconClassName, trend }: StatCardProps) {
    return (
        <div className="rounded-2xl border border-border bg-card p-6 shadow-xs">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">{title}</p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-foreground">
                        {typeof value === 'number' ? value.toLocaleString() : value}
                    </p>
                    {subtitle && <p className="mt-1 text-xs text-muted-foreground">{subtitle}</p>}
                    {trend && (
                        <p
                            className={cn(
                                'mt-2 text-xs font-medium',
                                trend.value >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
                            )}
                        >
                            {trend.value >= 0 ? '↑' : '↓'} {Math.abs(trend.value)}% {trend.label}
                        </p>
                    )}
                </div>
                <div
                    className={cn(
                        'flex size-12 items-center justify-center rounded-xl',
                        iconClassName ?? 'bg-primary/10 text-primary',
                    )}
                >
                    <Icon className="size-6" />
                </div>
            </div>
        </div>
    );
}
