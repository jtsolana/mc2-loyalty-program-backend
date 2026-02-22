import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search, ShoppingBag, Star, Users } from 'lucide-react';
import { useCallback, useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import { Pagination } from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CustomerRow, Paginator } from '@/types';
import admin from '@/routes/admin';

interface Props {
    customers: Paginator<CustomerRow>;
    filters: { search: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Customers', href: admin.customers.index().url },
];

export default function CustomersIndex({ customers, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            router.get(admin.customers.index().url, { search }, { preserveState: true, replace: true });
        },
        [search],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-blue-500/10">
                            <Users className="size-5 text-blue-600" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Customers</h1>
                            <p className="text-sm text-muted-foreground">{customers.total} total registered customers</p>
                        </div>
                    </div>

                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by name, email, phone…"
                                className="h-9 w-64 rounded-lg border border-input bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                        </div>
                        <Button type="submit" size="sm">
                            Search
                        </Button>
                    </form>
                </div>

                {/* Table */}
                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <DataTable
                        data={customers.data as unknown as Record<string, unknown>[]}
                        emptyMessage="No customers found."
                        columns={[
                            {
                                key: 'name',
                                header: 'Customer',
                                render: (row) => (
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-purple-500 text-sm font-bold text-white">
                                            {((row['name'] as string) ?? 'U').charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">{row['name'] as string}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {(row['username'] as string) ? `@${row['username'] as string}` : (row['email'] as string)}
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },
                            { key: 'email', header: 'Email', render: (row) => (row['email'] as string) ?? '—' },
                            { key: 'phone', header: 'Phone', render: (row) => (row['phone'] as string) ?? '—' },
                            {
                                key: 'purchases_count',
                                header: 'Purchases',
                                render: (row) => (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        <ShoppingBag className="size-3" />
                                        {row['purchases_count'] as number}
                                    </span>
                                ),
                            },
                            {
                                key: 'total_points',
                                header: 'Points (Balance)',
                                render: (row) => (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        <Star className="size-3" />
                                        {(row['total_points'] as number).toLocaleString()}
                                    </span>
                                ),
                            },
                            {
                                key: 'lifetime_points',
                                header: 'Lifetime Pts',
                                render: (row) => (
                                    <span className="text-sm text-muted-foreground">{(row['lifetime_points'] as number).toLocaleString()}</span>
                                ),
                            },
                            { key: 'created_at', header: 'Joined' },
                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <Link href={`/admin/customers/${row['hashed_id'] as string}`}>
                                        <Button variant="ghost" size="sm">
                                            <Eye className="size-4" />
                                            View
                                        </Button>
                                    </Link>
                                ),
                            },
                        ]}
                    />
                    <div className="px-4 pb-4">
                        <Pagination paginator={customers} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
