import { Head, router, useForm } from '@inertiajs/react';
import { Image, Megaphone, Pencil, Plus, Tag, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { RichTextEditor } from '@/components/admin/rich-text-editor';
import { DataTable } from '@/components/admin/data-table';
import { Pagination } from '@/components/admin/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Paginator, Promotion } from '@/types';
import admin from '@/routes/admin';

interface Props {
    promotions: Paginator<Promotion>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Promotions', href: admin.promotions.index().url },
];

type PromotionFormData = {
    title: string;
    excerpt: string;
    content: string;
    type: string;
    thumbnail: File | null;
    is_published: boolean;
};

function PromotionFormModal({
    open,
    onClose,
    editing,
}: {
    open: boolean;
    onClose: () => void;
    editing: Promotion | null;
}) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [thumbnailPreview, setThumbnailPreview] = useState<string | null>(editing?.thumbnail_url ?? null);

    const { data, setData, post, processing, errors, reset } = useForm<PromotionFormData>({
        title: editing?.title ?? '',
        excerpt: editing?.excerpt ?? '',
        content: editing?.content ?? '',
        type: editing?.type ?? 'promotion',
        thumbnail: null,
        is_published: editing?.is_published ?? false,
    });

    const handleThumbnailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        setData('thumbnail', file);
        if (file) {
            setThumbnailPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            post(admin.promotions.update({ promotion: editing.id }).url, {
                forceFormData: true,
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        } else {
            post(admin.promotions.store().url, {
                forceFormData: true,
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
            <DialogContent className="flex max-h-[90vh] flex-col sm:max-w-2xl">
                <DialogHeader className="shrink-0">
                    <DialogTitle>{editing ? 'Edit Promotion' : 'Create Promotion'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="flex flex-1 flex-col gap-4 overflow-y-auto pr-1">
                    <div className="grid gap-1.5">
                        <Label htmlFor="promo-title">Title</Label>
                        <Input
                            id="promo-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="e.g. Summer Sale — 20% Off All Drinks"
                            required
                        />
                        {errors.title && <p className="text-xs text-destructive">{errors.title}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="promo-excerpt">Excerpt</Label>
                        <Input
                            id="promo-excerpt"
                            value={data.excerpt}
                            onChange={(e) => setData('excerpt', e.target.value)}
                            placeholder="A short summary shown in listings..."
                            required
                        />
                        {errors.excerpt && <p className="text-xs text-destructive">{errors.excerpt}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="promo-type">Type</Label>
                        <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                            <SelectTrigger id="promo-type">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="promotion">Promotion</SelectItem>
                                <SelectItem value="announcement">Announcement</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Thumbnail</Label>
                        {thumbnailPreview && (
                            <img
                                src={thumbnailPreview}
                                alt="Thumbnail preview"
                                className="h-32 w-full rounded-md border border-border object-cover"
                            />
                        )}
                        <Input
                            ref={fileInputRef}
                            id="promo-thumbnail"
                            type="file"
                            accept="image/*"
                            onChange={handleThumbnailChange}
                            className="cursor-pointer"
                        />
                        {errors.thumbnail && <p className="text-xs text-destructive">{errors.thumbnail}</p>}
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Content</Label>
                        <RichTextEditor
                            key={editing?.id ?? 'new'}
                            value={data.content}
                            onChange={(v) => setData('content', v)}
                            placeholder="Write your full content here..."
                        />
                        {errors.content && <p className="text-xs text-destructive">{errors.content}</p>}
                    </div>

                    <div className="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                        <Checkbox
                            id="promo-published"
                            checked={data.is_published}
                            onCheckedChange={(checked) => setData('is_published', !!checked)}
                        />
                        <Label htmlFor="promo-published" className="cursor-pointer">
                            Published (visible to customers in the app)
                        </Label>
                    </div>

                    <div className="flex shrink-0 justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Save Changes' : 'Create'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PromotionsIndex({ promotions }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingPromotion, setEditingPromotion] = useState<Promotion | null>(null);

    const handleDelete = (promotion: Promotion) => {
        if (!confirm(`Delete "${promotion.title}"?`)) return;
        router.delete(admin.promotions.destroy({ promotion: promotion.id }).url, { preserveScroll: true });
    };

    const openCreate = () => {
        setEditingPromotion(null);
        setModalOpen(true);
    };

    const openEdit = (promotion: Promotion) => {
        setEditingPromotion(promotion);
        setModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Promotions & Announcements" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-purple-500/10">
                            <Megaphone className="size-5 text-purple-600" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Promotions & Announcements</h1>
                            <p className="text-sm text-muted-foreground">Publish promotions and announcements visible in the customer app</p>
                        </div>
                    </div>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Create New
                    </Button>
                </div>

                <div className="rounded-2xl border border-border bg-card shadow-xs">
                    <DataTable
                        data={promotions.data as unknown as Record<string, unknown>[]}
                        emptyMessage="No promotions yet. Create one to get started."
                        columns={[
                            {
                                key: 'thumbnail_url',
                                header: '',
                                render: (row) =>
                                    row['thumbnail_url'] ? (
                                        <img
                                            src={row['thumbnail_url'] as string}
                                            alt=""
                                            className="size-10 rounded-md object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-10 items-center justify-center rounded-md bg-muted">
                                            <Image className="size-4 text-muted-foreground" />
                                        </div>
                                    ),
                            },
                            {
                                key: 'title',
                                header: 'Title',
                                render: (row) => (
                                    <div>
                                        <p className="font-medium text-foreground">{row['title'] as string}</p>
                                        <p className="max-w-64 truncate text-xs text-muted-foreground">{row['excerpt'] as string}</p>
                                    </div>
                                ),
                            },
                            {
                                key: 'type',
                                header: 'Type',
                                render: (row) => (
                                    <span
                                        className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${
                                            row['type'] === 'promotion'
                                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                        }`}
                                    >
                                        <Tag className="size-3" />
                                        {row['type'] as string}
                                    </span>
                                ),
                            },
                            {
                                key: 'is_published',
                                header: 'Status',
                                render: (row) =>
                                    row['is_published'] ? (
                                        <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            Published
                                        </span>
                                    ) : (
                                        <span className="inline-flex rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                            Draft
                                        </span>
                                    ),
                            },
                            {
                                key: 'created_at',
                                header: 'Created',
                                render: (row) => <span className="text-sm text-muted-foreground">{row['created_at'] as string}</span>,
                            },
                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <div className="flex items-center gap-1">
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(row as unknown as Promotion)}>
                                            <Pencil className="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleDelete(row as unknown as Promotion)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ),
                            },
                        ]}
                    />
                </div>

                {promotions.last_page > 1 && <Pagination paginator={promotions} />}
            </div>

            <PromotionFormModal
                key={editingPromotion?.id ?? 'new'}
                open={modalOpen}
                onClose={() => setModalOpen(false)}
                editing={editingPromotion}
            />
        </AppLayout>
    );
}
