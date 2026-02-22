import { Head, Link } from '@inertiajs/react';
import { BarChart3, Eye, ShoppingBag, Star, TrendingUp, Users } from 'lucide-react';
import ReactApexChart from 'react-apexcharts';
import type { ApexOptions } from 'apexcharts';
import { StatCard } from '@/components/admin/stat-card';
import { DataTable } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CustomerRow, DashboardStats, MonthlyPurchase } from '@/types';
import admin from '@/routes/admin';

interface Props {
    stats: DashboardStats;
    recentCustomers: CustomerRow[];
    monthlyPurchases: MonthlyPurchase[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin Dashboard', href: admin.dashboard().url }];

export default function AdminDashboard({ stats, recentCustomers, monthlyPurchases }: Props) {
    const chartCategories = monthlyPurchases.map((m) => m.month);
    const revenueData = monthlyPurchases.map((m) => parseFloat(m.revenue ?? '0'));
    const pointsData = monthlyPurchases.map((m) => m.points ?? 0);

    const revenueChartOptions: ApexOptions = {
        chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit' },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0 } },
        dataLabels: { enabled: false },
        xaxis: { categories: chartCategories, labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => `₱${v.toLocaleString()}` } },
        colors: ['#3b82f6'],
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        tooltip: { y: { formatter: (v) => `₱${v.toLocaleString()}` } },
    };

    const pointsChartOptions: ApexOptions = {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: chartCategories, labels: { style: { fontSize: '12px' } } },
        colors: ['#8b5cf6'],
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Total Customers"
                        value={stats.total_customers}
                        icon={Users}
                        iconClassName="bg-blue-500/10 text-blue-600 dark:text-blue-400"
                    />
                    <StatCard
                        title="Total Purchases"
                        value={stats.total_purchases}
                        icon={ShoppingBag}
                        iconClassName="bg-green-500/10 text-green-600 dark:text-green-400"
                    />
                    <StatCard
                        title="Points Issued"
                        value={stats.total_points_issued.toLocaleString()}
                        icon={Star}
                        iconClassName="bg-yellow-500/10 text-yellow-600 dark:text-yellow-400"
                    />
                    <StatCard
                        title="Redemptions"
                        value={stats.total_redemptions}
                        icon={TrendingUp}
                        iconClassName="bg-purple-500/10 text-purple-600 dark:text-purple-400"
                    />
                </div>

                {/* Charts */}
                {monthlyPurchases.length > 0 && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="rounded-2xl border border-border bg-card p-6 shadow-xs">
                            <div className="mb-4 flex items-center gap-2">
                                <BarChart3 className="size-5 text-blue-500" />
                                <h3 className="font-semibold text-foreground">Monthly Revenue</h3>
                            </div>
                            <ReactApexChart
                                options={revenueChartOptions}
                                series={[{ name: 'Revenue', data: revenueData }]}
                                type="area"
                                height={220}
                            />
                        </div>

                        <div className="rounded-2xl border border-border bg-card p-6 shadow-xs">
                            <div className="mb-4 flex items-center gap-2">
                                <Star className="size-5 text-purple-500" />
                                <h3 className="font-semibold text-foreground">Points Earned per Month</h3>
                            </div>
                            <ReactApexChart
                                options={pointsChartOptions}
                                series={[{ name: 'Points', data: pointsData }]}
                                type="bar"
                                height={220}
                            />
                        </div>
                    </div>
                )}

                {/* Recent Customers */}
                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <div className="flex items-center justify-between border-b border-border px-6 py-4">
                        <h3 className="font-semibold text-foreground">Recent Customers</h3>
                        <Link href={admin.customers.index().url}>
                            <Button variant="outline" size="sm">
                                View All
                            </Button>
                        </Link>
                    </div>
                    <div className="p-4">
                        <DataTable
                            data={recentCustomers as unknown as Record<string, unknown>[]}
                            emptyMessage="No customers yet."
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Customer',
                                    render: (row) => (
                                        <div>
                                            <p className="font-medium">{row['name'] as string}</p>
                                            <p className="text-xs text-muted-foreground">{(row['email'] as string) ?? (row['username'] as string)}</p>
                                        </div>
                                    ),
                                },
                                { key: 'phone', header: 'Phone', render: (row) => (row['phone'] as string) ?? '—' },
                                {
                                    key: 'purchases_count',
                                    header: 'Purchases',
                                    render: (row) => (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                            <ShoppingBag className="size-3" />
                                            {row['purchases_count'] as number}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'total_points',
                                    header: 'Points',
                                    render: (row) => (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                                            <Star className="size-3" />
                                            {(row['total_points'] as number).toLocaleString()}
                                        </span>
                                    ),
                                },
                                { key: 'created_at', header: 'Joined', render: (row) => row['created_at'] as string },
                                {
                                    key: 'actions',
                                    header: '',
                                    render: (row) => (
                                        <Link href={`/admin/customers/${row['hashed_id'] as string}`}>
                                            <Button variant="ghost" size="sm">
                                                <Eye className="size-4" />
                                            </Button>
                                        </Link>
                                    ),
                                },
                            ]}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
