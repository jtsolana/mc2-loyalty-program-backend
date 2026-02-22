import { Head, useForm } from '@inertiajs/react';
import { Building2, Mail, MapPin, Phone, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CompanyProfile } from '@/types';
import admin from '@/routes/admin';

interface Props {
    company: CompanyProfile;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Company Profile', href: admin.companyProfile.edit().url },
];

type CompanyFormData = {
    name: string;
    logo: File | null;
    address: string;
    contact_number: string;
    email: string;
};

export default function CompanyProfileEdit({ company }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(company.logo_url);

    const { data, setData, post, processing, errors } = useForm<CompanyFormData>({
        name: company.name ?? '',
        logo: null,
        address: company.address ?? '',
        contact_number: company.contact_number ?? '',
        email: company.email ?? '',
    });

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        setData('logo', file);
        if (file) {
            setLogoPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.companyProfile.update().url, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Company Profile" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-xl bg-blue-500/10">
                        <Building2 className="size-5 text-blue-600" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-foreground">Company Profile</h1>
                        <p className="text-sm text-muted-foreground">
                            This information appears in the sidebar header and login page
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="max-w-2xl">
                    <div className="rounded-2xl border border-border bg-card shadow-xs">
                        {/* Logo Section */}
                        <div className="border-b border-border px-6 py-5">
                            <h2 className="mb-4 text-sm font-semibold text-foreground">Logo</h2>
                            <div className="flex items-center gap-5">
                                <div className="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted">
                                    {logoPreview ? (
                                        <img src={logoPreview} alt="Company logo" className="size-20 object-cover" />
                                    ) : (
                                        <Building2 className="size-8 text-muted-foreground" />
                                    )}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        <Upload className="size-4" />
                                        {logoPreview ? 'Change Logo' : 'Upload Logo'}
                                    </Button>
                                    <p className="text-xs text-muted-foreground">PNG, JPG up to 2MB</p>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/*"
                                        onChange={handleLogoChange}
                                        className="hidden"
                                    />
                                    {errors.logo && <p className="text-xs text-destructive">{errors.logo}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Details Section */}
                        <div className="flex flex-col gap-5 px-6 py-5">
                            <div className="grid gap-1.5">
                                <Label htmlFor="company-name">Company Name</Label>
                                <Input
                                    id="company-name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Acme Corporation"
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="company-address">
                                    <span className="flex items-center gap-1.5">
                                        <MapPin className="size-3.5 text-muted-foreground" />
                                        Address
                                    </span>
                                </Label>
                                <Input
                                    id="company-address"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    placeholder="123 Main St, City, Country"
                                />
                                {errors.address && <p className="text-xs text-destructive">{errors.address}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="company-phone">
                                        <span className="flex items-center gap-1.5">
                                            <Phone className="size-3.5 text-muted-foreground" />
                                            Contact Number
                                        </span>
                                    </Label>
                                    <Input
                                        id="company-phone"
                                        value={data.contact_number}
                                        onChange={(e) => setData('contact_number', e.target.value)}
                                        placeholder="+1 (555) 000-0000"
                                    />
                                    {errors.contact_number && <p className="text-xs text-destructive">{errors.contact_number}</p>}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="company-email">
                                        <span className="flex items-center gap-1.5">
                                            <Mail className="size-3.5 text-muted-foreground" />
                                            Email
                                        </span>
                                    </Label>
                                    <Input
                                        id="company-email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="hello@company.com"
                                    />
                                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end border-t border-border px-6 py-4">
                            <Button type="submit" disabled={processing}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
