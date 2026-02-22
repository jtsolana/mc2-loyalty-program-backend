import { Head, router, useForm } from '@inertiajs/react';
import { CupSoda, Pencil, Plus, Star, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PointRule, PointRuleType, RuleTypeOption } from '@/types';
import admin from '@/routes/admin';

interface Props {
    rules: PointRule[];
    ruleTypes: RuleTypeOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Point Rules', href: admin.pointRules.index().url },
];

type RuleFormData = {
    name: string;
    type: PointRuleType;
    spend_amount: string;
    minimum_spend: string;
    points_per_unit: string;
    points_per_item: string;
    is_active: boolean;
};

function RuleFormModal({
    open,
    onClose,
    editing,
    ruleTypes,
}: {
    open: boolean;
    onClose: () => void;
    editing: PointRule | null;
    ruleTypes: RuleTypeOption[];
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<RuleFormData>({
        name: editing?.name ?? '',
        type: editing?.type ?? 'spend_based',
        spend_amount: editing?.spend_amount ?? '50',
        minimum_spend: editing?.minimum_spend ?? '0',
        points_per_unit: String(editing?.points_per_unit ?? 1),
        points_per_item: String(editing?.points_per_item ?? 1),
        is_active: editing?.is_active ?? true,
    });

    useEffect(() => {
        setData({
            name: editing?.name ?? '',
            type: editing?.type ?? 'spend_based',
            spend_amount: editing?.spend_amount ?? '50',
            minimum_spend: editing?.minimum_spend ?? '0',
            points_per_unit: String(editing?.points_per_unit ?? 1),
            points_per_item: String(editing?.points_per_item ?? 1),
            is_active: editing?.is_active ?? true,
        });
    }, [editing]);

    const isPerItem = data.type === 'per_item';

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(admin.pointRules.update({ pointRule: editing.hashed_id }).url, {
                onSuccess: () => { reset(); onClose(); },
            });
        } else {
            post(admin.pointRules.store().url, {
                onSuccess: () => { reset(); onClose(); },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Edit Point Rule' : 'Create Point Rule'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="rule-name">Rule Name</Label>
                        <Input
                            id="rule-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Standard Earn Rule"
                            required
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Earning Type</Label>
                        <div className="grid grid-cols-2 gap-2">
                            {ruleTypes.map((rt) => (
                                <button
                                    key={rt.value}
                                    type="button"
                                    onClick={() => setData('type', rt.value)}
                                    className={`flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm transition-colors ${
                                        data.type === rt.value
                                            ? 'border-primary bg-primary/5 font-medium text-primary'
                                            : 'border-border text-muted-foreground hover:border-muted-foreground'
                                    }`}
                                >
                                    {rt.value === 'per_item' ? (
                                        <CupSoda className="size-4 shrink-0" />
                                    ) : (
                                        <Star className="size-4 shrink-0" />
                                    )}
                                    {rt.label}
                                </button>
                            ))}
                        </div>
                        {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
                    </div>

                    {isPerItem ? (
                        <div className="grid gap-1.5">
                            <Label htmlFor="points-per-item">Points per Item / Drink</Label>
                            <Input
                                id="points-per-item"
                                type="number"
                                min="1"
                                value={data.points_per_item}
                                onChange={(e) => setData('points_per_item', e.target.value)}
                                required
                            />
                            <p className="text-xs text-muted-foreground">Number of points earned for each drink or item ordered.</p>
                            {errors.points_per_item && <p className="text-xs text-destructive">{errors.points_per_item}</p>}
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="spend-amount">Spend per Point (₱)</Label>
                                    <Input
                                        id="spend-amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={data.spend_amount}
                                        onChange={(e) => setData('spend_amount', e.target.value)}
                                        required
                                    />
                                    {errors.spend_amount && <p className="text-xs text-destructive">{errors.spend_amount}</p>}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="points-per-unit">Points per Unit</Label>
                                    <Input
                                        id="points-per-unit"
                                        type="number"
                                        min="1"
                                        value={data.points_per_unit}
                                        onChange={(e) => setData('points_per_unit', e.target.value)}
                                        required
                                    />
                                    {errors.points_per_unit && <p className="text-xs text-destructive">{errors.points_per_unit}</p>}
                                </div>
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="minimum-spend">Minimum Spend (₱)</Label>
                                <Input
                                    id="minimum-spend"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.minimum_spend}
                                    onChange={(e) => setData('minimum_spend', e.target.value)}
                                    required
                                />
                                <p className="text-xs text-muted-foreground">Minimum purchase amount to qualify for points (0 = no minimum)</p>
                                {errors.minimum_spend && <p className="text-xs text-destructive">{errors.minimum_spend}</p>}
                            </div>
                        </>
                    )}

                    <div className="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                        <input
                            id="is-active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="size-4 rounded"
                        />
                        <Label htmlFor="is-active" className="cursor-pointer">
                            Active (rule will be applied to purchases)
                        </Label>
                    </div>

                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm dark:border-yellow-800 dark:bg-yellow-900/20">
                        <p className="font-medium text-yellow-800 dark:text-yellow-200">Preview</p>
                        <p className="mt-0.5 text-yellow-700 dark:text-yellow-300">
                            {isPerItem ? (
                                <>
                                    Customer earns <strong>{data.points_per_item || '?'} point(s)</strong> per drink / item ordered.
                                </>
                            ) : (
                                <>
                                    Customer earns <strong>{data.points_per_unit} point(s)</strong> for every{' '}
                                    <strong>₱{data.spend_amount}</strong> spent
                                    {parseFloat(data.minimum_spend) > 0 && ` (min. ₱${data.minimum_spend})`}.
                                </>
                            )}
                        </p>
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Save Changes' : 'Create Rule'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PointRulesIndex({ rules, ruleTypes }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRule, setEditingRule] = useState<PointRule | null>(null);

    const handleDelete = (rule: PointRule) => {
        if (!confirm(`Delete rule "${rule.name}"?`)) return;
        router.delete(admin.pointRules.destroy({ pointRule: rule.hashed_id }).url, { preserveScroll: true });
    };

    const openCreate = () => {
        setEditingRule(null);
        setModalOpen(true);
    };

    const openEdit = (rule: PointRule) => {
        setEditingRule(rule);
        setModalOpen(true);
    };

    const ruleDescription = (rule: PointRule): string => {
        if (rule.type === 'per_item') {
            return `${rule.points_per_item} pt(s) / item`;
        }
        return `₱${parseFloat(rule.spend_amount ?? '0').toLocaleString()} → ${rule.points_per_unit} pt(s)`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Point Rules" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-yellow-500/10">
                            <Star className="size-5 text-yellow-600" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Point Rules</h1>
                            <p className="text-sm text-muted-foreground">Configure how customers earn points per purchase</p>
                        </div>
                    </div>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Add Rule
                    </Button>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-800 dark:bg-blue-900/20">
                    <p className="font-medium text-blue-800 dark:text-blue-200">How point rules work</p>
                    <p className="mt-1 text-blue-700 dark:text-blue-300">
                        <strong>Per Spend Amount</strong> — points are calculated based on the total bill (e.g. ₱50 spent = 1 point).
                        <br />
                        <strong>Per Item / Drink</strong> — a fixed number of points is awarded for each individual item or drink ordered.
                    </p>
                </div>

                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <DataTable
                        data={rules as unknown as Record<string, unknown>[]}
                        emptyMessage="No point rules yet. Create one to start rewarding customers."
                        columns={[
                            {
                                key: 'name',
                                header: 'Rule Name',
                                render: (row) => <span className="font-medium text-foreground">{row['name'] as string}</span>,
                            },
                            {
                                key: 'type',
                                header: 'Type',
                                render: (row) =>
                                    row['type'] === 'per_item' ? (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                            <CupSoda className="size-3" /> Per Item
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                            <Star className="size-3" /> Per Spend
                                        </span>
                                    ),
                            },
                            {
                                key: 'earning',
                                header: 'Earning Formula',
                                render: (row) => (
                                    <span className="text-sm text-muted-foreground">
                                        {ruleDescription(row as unknown as PointRule)}
                                    </span>
                                ),
                            },
                            {
                                key: 'is_active',
                                header: 'Status',
                                render: (row) =>
                                    row['is_active'] ? (
                                        <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            Active
                                        </span>
                                    ) : (
                                        <span className="inline-flex rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                            Inactive
                                        </span>
                                    ),
                            },
                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <div className="flex items-center gap-1">
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(row as unknown as PointRule)}>
                                            <Pencil className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleDelete(row as unknown as PointRule)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ),
                            },
                        ]}
                    />
                </div>
            </div>

            <RuleFormModal open={modalOpen} onClose={() => setModalOpen(false)} editing={editingRule} ruleTypes={ruleTypes} />
        </AppLayout>
    );
}
