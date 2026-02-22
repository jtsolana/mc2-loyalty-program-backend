import { Head, router, useForm } from '@inertiajs/react';
import { Gift, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, RewardRule } from '@/types';
import admin from '@/routes/admin';

interface Props {
    rules: RewardRule[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Reward Rules', href: admin.rewardRules.index().url },
];

type RuleFormData = {
    name: string;
    reward_title: string;
    points_required: string;
    expires_in_days: string;
    is_active: boolean;
};

function RuleFormModal({
    open,
    onClose,
    editing,
}: {
    open: boolean;
    onClose: () => void;
    editing: RewardRule | null;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<RuleFormData>({
        name: editing?.name ?? '',
        reward_title: editing?.reward_title ?? '',
        points_required: String(editing?.points_required ?? 500),
        expires_in_days: String(editing?.expires_in_days ?? 30),
        is_active: editing?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(admin.rewardRules.update({ rewardRule: editing.id }).url, {
                onSuccess: () => { reset(); onClose(); },
            });
        } else {
            post(admin.rewardRules.store().url, {
                onSuccess: () => { reset(); onClose(); },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Edit Reward Rule' : 'Create Reward Rule'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="rule-name">Rule Name</Label>
                        <Input
                            id="rule-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Free Drink Reward"
                            required
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="reward-title">Reward Title</Label>
                        <Input
                            id="reward-title"
                            value={data.reward_title}
                            onChange={(e) => setData('reward_title', e.target.value)}
                            placeholder="e.g. 1 Free Regular Drink"
                            required
                        />
                        {errors.reward_title && <p className="text-xs text-destructive">{errors.reward_title}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="points-required">Points Required</Label>
                            <Input
                                id="points-required"
                                type="number"
                                min="1"
                                value={data.points_required}
                                onChange={(e) => setData('points_required', e.target.value)}
                                required
                            />
                            {errors.points_required && <p className="text-xs text-destructive">{errors.points_required}</p>}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="expires-in-days">Expires In (days)</Label>
                            <Input
                                id="expires-in-days"
                                type="number"
                                min="1"
                                value={data.expires_in_days}
                                onChange={(e) => setData('expires_in_days', e.target.value)}
                                required
                            />
                            {errors.expires_in_days && <p className="text-xs text-destructive">{errors.expires_in_days}</p>}
                        </div>
                    </div>

                    <div className="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                        <input
                            id="is-active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="size-4 rounded"
                        />
                        <Label htmlFor="is-active" className="cursor-pointer">
                            Active (rewards will be issued automatically)
                        </Label>
                    </div>

                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm dark:border-green-800 dark:bg-green-900/20">
                        <p className="font-medium text-green-800 dark:text-green-200">Preview</p>
                        <p className="mt-0.5 text-green-700 dark:text-green-300">
                            Customer earns <strong>{data.reward_title || '?'}</strong> when they reach{' '}
                            <strong>{data.points_required || '?'} points</strong>. Reward expires in{' '}
                            <strong>{data.expires_in_days || '?'} day(s)</strong>.
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

export default function RewardRulesIndex({ rules }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRule, setEditingRule] = useState<RewardRule | null>(null);

    const handleDelete = (rule: RewardRule) => {
        if (!confirm(`Delete reward rule "${rule.name}"?`)) return;
        router.delete(admin.rewardRules.destroy({ rewardRule: rule.id }).url, { preserveScroll: true });
    };

    const openCreate = () => {
        setEditingRule(null);
        setModalOpen(true);
    };

    const openEdit = (rule: RewardRule) => {
        setEditingRule(rule);
        setModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reward Rules" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-green-500/10">
                            <Gift className="size-5 text-green-600" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Reward Rules</h1>
                            <p className="text-sm text-muted-foreground">Configure rewards customers earn when reaching point thresholds</p>
                        </div>
                    </div>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Add Rule
                    </Button>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-800 dark:bg-blue-900/20">
                    <p className="font-medium text-blue-800 dark:text-blue-200">How reward rules work</p>
                    <p className="mt-1 text-blue-700 dark:text-blue-300">
                        When a customer's points reach the threshold, a reward is automatically issued and their points are deducted.
                        Staff can mark rewards as claimed when the customer uses them. Unclaimed rewards expire after the set number of days.
                    </p>
                </div>

                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <DataTable
                        data={rules as unknown as Record<string, unknown>[]}
                        emptyMessage="No reward rules yet. Create one to start rewarding customers."
                        columns={[
                            {
                                key: 'name',
                                header: 'Rule Name',
                                render: (row) => <span className="font-medium text-foreground">{row['name'] as string}</span>,
                            },
                            {
                                key: 'reward_title',
                                header: 'Reward',
                                render: (row) => (
                                    <span className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                        <Gift className="size-3.5 shrink-0" />
                                        {row['reward_title'] as string}
                                    </span>
                                ),
                            },
                            {
                                key: 'points_required',
                                header: 'Points Required',
                                render: (row) => (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        {row['points_required'] as number} pts
                                    </span>
                                ),
                            },
                            {
                                key: 'expires_in_days',
                                header: 'Expires In',
                                render: (row) => (
                                    <span className="text-sm text-muted-foreground">
                                        {row['expires_in_days'] as number} day(s)
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
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(row as unknown as RewardRule)}>
                                            <Pencil className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleDelete(row as unknown as RewardRule)}
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

            <RuleFormModal open={modalOpen} onClose={() => setModalOpen(false)} editing={editingRule} />
        </AppLayout>
    );
}
