<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    public function edit(): Response
    {
        $company = CompanyProfile::getSingleton();

        return Inertia::render('admin/company-profile/edit', [
            'company' => [
                'name' => $company->name,
                'logo_url' => $company->logo_url,
                'address' => $company->address,
                'contact_number' => $company->contact_number,
                'email' => $company->email,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $company = CompanyProfile::getSingleton();

        $data = $request->only(['name', 'address', 'contact_number', 'email']);

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('company', 'public');
        }

        $company->update($data);

        return back()->with('success', 'Company profile updated successfully.');
    }
}
