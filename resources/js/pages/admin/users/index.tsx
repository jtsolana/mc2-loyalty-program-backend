import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2, Users2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import { Pagination } from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Paginator, Role, UserRow } from '@/types';
import admin from '@/routes/admin';

interface Props {
    users: Paginator<UserRow>;
    roles: Role[];
    filters: { search: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Users', href: admin.users.index().url },
];

type UserFormData = {
    name: string;
    username: string;
    email: string;
    password: string;
    role: string;
};

function UserFormModal({
    open,
    onClose,
    editing,
    roles,
}: {
    open: boolean;
    onClose: () => void;
    editing: UserRow | null;
    roles: Role[];
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<UserFormData>({
        name: editing?.name ?? '',
        username: editing?.username ?? '',
        email: editing?.email ?? '',
        password: '',
        role: editing?.roles?.[0] ?? (roles[0]?.name ?? ''),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(admin.users.update({ user: editing.id }).url, {
                onSuccess: () => { reset(); onClose(); },
            });
        } else {
            post(admin.users.store().url, {
                onSuccess: () => { reset(); onClose(); },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{editing ? 'Edit User' : 'Create User'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="name">Full Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="username">Username</Label>
                        <Input id="username" value={data.username} onChange={(e) => setData('username', e.target.value)} required />
                        {errors.username && <p className="text-xs text-destructive">{errors.username}</p>}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                        {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="password">{editing ? 'New Password (leave blank to keep)' : 'Password'}</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required={!editing}
                        />
                        {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="role">Role</Label>
                        <select
                            id="role"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            {roles.map((r) => (
                                <option key={r.id} value={r.name}>
                                    {r.display_name}
                                </option>
                            ))}
                        </select>
                        {errors.role && <p className="text-xs text-destructive">{errors.role}</p>}
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Save Changes' : 'Create User'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({ users, roles, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserRow | null>(null);

    const handleSearch = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            router.get(admin.users.index().url, { search }, { preserveState: true, replace: true });
        },
        [search],
    );

    const handleDelete = (user: UserRow) => {
        if (!confirm(`Delete user "${user.name}"? This cannot be undone.`)) return;
        router.delete(admin.users.destroy({ user: user.id }).url, { preserveScroll: true });
    };

    const openCreate = () => {
        setEditingUser(null);
        setModalOpen(true);
    };

    const openEdit = (user: UserRow) => {
        setEditingUser(user);
        setModalOpen(true);
    };

    const roleBadgeClass = (role: string) =>
        role === 'admin'
            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
            : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-indigo-500/10">
                            <Users2 className="size-5 text-indigo-600" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Users</h1>
                            <p className="text-sm text-muted-foreground">Admin &amp; Staff accounts</p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search users…"
                                    className="h-9 w-52 rounded-lg border border-input bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                />
                            </div>
                            <Button type="submit" variant="outline" size="sm">
                                Search
                            </Button>
                        </form>
                        <Button size="sm" onClick={openCreate}>
                            <Plus className="size-4" />
                            Add User
                        </Button>
                    </div>
                </div>

                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <DataTable
                        data={users.data as unknown as Record<string, unknown>[]}
                        emptyMessage="No users found."
                        columns={[
                            {
                                key: 'name',
                                header: 'User',
                                render: (row) => (
                                    <div>
                                        <p className="font-medium text-foreground">{row['name'] as string}</p>
                                        <p className="text-xs text-muted-foreground">@{row['username'] as string}</p>
                                    </div>
                                ),
                            },
                            { key: 'email', header: 'Email' },
                            {
                                key: 'roles',
                                header: 'Role',
                                render: (row) => (
                                    <div className="flex flex-wrap gap-1">
                                        {((row['roles'] as string[]) ?? []).map((r) => (
                                            <span key={r} className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${roleBadgeClass(r)}`}>
                                                {r}
                                            </span>
                                        ))}
                                    </div>
                                ),
                            },
                            { key: 'created_at', header: 'Created' },
                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <div className="flex items-center gap-1">
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(row as unknown as UserRow)}>
                                            <Pencil className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleDelete(row as unknown as UserRow)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ),
                            },
                        ]}
                    />
                    <div className="px-4 pb-4">
                        <Pagination paginator={users} />
                    </div>
                </div>
            </div>

            <UserFormModal
                open={modalOpen}
                onClose={() => setModalOpen(false)}
                editing={editingUser}
                roles={roles}
            />
        </AppLayout>
    );
}
