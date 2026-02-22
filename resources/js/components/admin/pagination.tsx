import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginator } from '@/types';

interface PaginationProps<T> {
    paginator: Paginator<T>;
}

export function Pagination<T>({ paginator }: PaginationProps<T>) {
    if (paginator.last_page <= 1) return null;

    return (
        <div className="flex items-center justify-between border-t border-border px-1 pt-4 text-sm text-muted-foreground">
            <p>
                Showing {paginator.from ?? 0}–{paginator.to ?? 0} of {paginator.total} results
            </p>
            <div className="flex items-center gap-1">
                {paginator.links.map((link, i) => {
                    if (link.label.includes('Previous')) {
                        return (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                className={cn(
                                    'flex size-8 items-center justify-center rounded-lg border border-border',
                                    !link.url && 'pointer-events-none opacity-40',
                                    'hover:bg-muted',
                                )}
                                preserveScroll
                            >
                                <ChevronLeft className="size-4" />
                            </Link>
                        );
                    }
                    if (link.label.includes('Next')) {
                        return (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                className={cn(
                                    'flex size-8 items-center justify-center rounded-lg border border-border',
                                    !link.url && 'pointer-events-none opacity-40',
                                    'hover:bg-muted',
                                )}
                                preserveScroll
                            >
                                <ChevronRight className="size-4" />
                            </Link>
                        );
                    }
                    return (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={cn(
                                'flex size-8 items-center justify-center rounded-lg border border-border text-xs font-medium',
                                link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                                !link.url && 'pointer-events-none opacity-40',
                            )}
                            preserveScroll
                        >
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
