import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Receipt, ShoppingBag, Star, TrendingDown, TrendingUp } from 'lucide-react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DataTable } from '@/components/admin/data-table';
import { StatCard } from '@/components/admin/stat-card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PointTransaction, Purchase, PurchaseLineItem } from '@/types';
import admin from '@/routes/admin';

interface Customer {
    id: number;
    name: string;
    username: string | null;
    email: string | null;
    phone: string | null;
    avatar: string | null;
    total_points: number;
    lifetime_points: number;
    created_at: string;
}

interface Props {
    customer: Customer;
    purchases: Purchase[];
    transactions: PointTransaction[];
}

const formatDate = (value: string) => {
    const d = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

export default function CustomerShow({ customer, purchases, transactions }: Props) {
    const [receiptPurchase, setReceiptPurchase] = useState<Purchase | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: admin.dashboard().url },
        { title: 'Customers', href: admin.customers.index().url },
        { title: customer.name, href: admin.customers.show({ user: customer.id }).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={customer.name} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Back + Header */}
                <div className="flex items-center gap-4">
                    <Link href={admin.customers.index().url}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div className="flex items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-purple-500 text-lg font-bold text-white">
                            {customer.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">{customer.name}</h1>
                            <p className="text-sm text-muted-foreground">
                                {customer.username ? `@${customer.username}` : customer.email} · Member since {customer.created_at}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        title="Point Balance"
                        value={customer.total_points.toLocaleString()}
                        icon={Star}
                        iconClassName="bg-yellow-500/10 text-yellow-600"
                    />
                    <StatCard
                        title="Lifetime Points"
                        value={customer.lifetime_points.toLocaleString()}
                        icon={TrendingUp}
                        iconClassName="bg-green-500/10 text-green-600"
                    />
                    <StatCard
                        title="Total Purchases"
                        value={purchases.length}
                        icon={ShoppingBag}
                        iconClassName="bg-blue-500/10 text-blue-600"
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Purchase History */}
                    <div className="rounded-2xl border border-border bg-card shadow-xs">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="flex items-center gap-2 font-semibold text-foreground">
                                <ShoppingBag className="size-4 text-blue-500" />
                                Purchase History
                            </h3>
                        </div>
                        <div className="p-4">
                            <DataTable
                                data={purchases as unknown as Record<string, unknown>[]}
                                emptyMessage="No purchases yet."
                                columns={[
                                    {
                                        key: 'loyverse_receipt_id',
                                        header: 'Receipt',
                                        render: (row) => (
                                            <button
                                                type="button"
                                                className="font-mono text-xs font-bold cursor-pointer text-primary underline-offset-2 hover:underline"
                                                onClick={() => setReceiptPurchase(row as unknown as Purchase)}
                                            >
                                                {row['loyverse_receipt_id'] as string}
                                            </button>
                                        ),
                                    },
                                    {
                                        key: 'total_amount',
                                        header: 'Amount',
                                        render: (row) => `₱${parseFloat(row['total_amount'] as string).toLocaleString()}`,
                                    },
                                    {
                                        key: 'points_earned',
                                        header: 'Points',
                                        render: (row) => (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">
                                                +{row['points_earned'] as number}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: 'status',
                                        header: 'Status',
                                        render: (row) => (
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                                                    row['status'] === 'completed'
                                                        ? 'bg-green-100 text-green-700'
                                                        : row['status'] === 'cancelled'
                                                          ? 'bg-red-100 text-red-700'
                                                          : 'bg-yellow-100 text-yellow-700'
                                                }`}
                                            >
                                                {row['status'] as string}
                                            </span>
                                        ),
                                    },
                                    { key: 'created_at', header: 'Date', render: (row) => formatDate(row['created_at'] as string) },
                                ]}
                            />
                        </div>
                    </div>

                    {/* Point Transactions */}
                    <div className="rounded-2xl border border-border bg-card shadow-xs">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="flex items-center gap-2 font-semibold text-foreground">
                                <Star className="size-4 text-yellow-500" />
                                Point Transactions
                            </h3>
                        </div>
                        <div className="p-4">
                            <DataTable
                                data={transactions as unknown as Record<string, unknown>[]}
                                emptyMessage="No transactions yet."
                                columns={[
                                    {
                                        key: 'type',
                                        header: 'Type',
                                        render: (row) => (
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                                                    row['type'] === 'earn'
                                                        ? 'bg-green-100 text-green-700'
                                                        : row['type'] === 'redeem'
                                                          ? 'bg-red-100 text-red-700'
                                                          : 'bg-gray-100 text-gray-700'
                                                }`}
                                            >
                                                {row['type'] === 'earn' ? <TrendingUp className="size-3" /> : <TrendingDown className="size-3" />}
                                                {row['type'] as string}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: 'points',
                                        header: 'Points',
                                        render: (row) => (
                                            <span
                                                className={`font-semibold ${(row['points'] as number) > 0 ? 'text-green-600' : 'text-red-600'}`}
                                            >
                                                {(row['points'] as number) > 0 ? '+' : ''}
                                                {row['points'] as number}
                                            </span>
                                        ),
                                    },
                                    { key: 'balance_after', header: 'Balance', render: (row) => (row['balance_after'] as number).toLocaleString() },
                                    { key: 'description', header: 'Description', className: 'max-w-4xl truncate' },
                                    { key: 'created_at', header: 'Date', render: (row) => formatDate(row['created_at'] as string), className: 'max-w-4xl truncate' },
                                ]}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={!!receiptPurchase} onOpenChange={(v) => !v && setReceiptPurchase(null)}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Receipt className="size-4" />
                            Receipt #{receiptPurchase?.loyverse_receipt_id}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-xs text-muted-foreground">
                                    <th className="pb-2 font-medium">Item</th>
                                    <th className="pb-2 font-medium">Variant</th>
                                    <th className="pb-2 font-medium">SKU</th>
                                    <th className="pb-2 text-center font-medium">Qty</th>
                                    <th className="pb-2 text-right font-medium">Price</th>
                                    <th className="pb-2 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(receiptPurchase?.loyverse_payload?.line_items ?? []).map((item: PurchaseLineItem, i: number) => (
                                    <tr key={i} className="border-b border-border/50 last:border-0">
                                        <td className="py-2 font-medium">{item.item_name}</td>
                                        <td className="py-2 font-medium">{item.variant_name}</td>
                                        <td className="py-2 font-mono text-xs text-muted-foreground">{item.sku ?? '—'}</td>
                                        <td className="py-2 text-center">{item.quantity}</td>
                                        <td className="py-2 text-right">₱{item.price.toLocaleString()}</td>
                                        <td className="py-2 text-right font-medium">₱{item.total_money.toLocaleString()}</td>
                                    </tr>
                                ))}
                                {!(receiptPurchase?.loyverse_payload?.line_items?.length) && (
                                    <tr>
                                        <td colSpan={5} className="py-6 text-center text-muted-foreground">No line items found.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
