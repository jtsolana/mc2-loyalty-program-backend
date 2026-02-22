<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/promotions/index', [
            'promotions' => Promotion::latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePromotion($request);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('promotions', 'public');
        }

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        Promotion::create($data);

        return back()->with('success', 'Promotion created successfully.');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validatePromotion($request);

        if ($request->hasFile('thumbnail')) {
            if ($promotion->thumbnail) {
                Storage::disk('public')->delete($promotion->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('promotions', 'public');
        }

        if ($data['is_published'] && ! $promotion->published_at) {
            $data['published_at'] = now();
        } elseif (! $data['is_published']) {
            $data['published_at'] = null;
        }

        $promotion->update($data);

        return back()->with('success', 'Promotion updated successfully.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->thumbnail) {
            Storage::disk('public')->delete($promotion->thumbnail);
        }

        $promotion->delete();

        return back()->with('success', 'Promotion deleted.');
    }

    /** @return array<string, mixed> */
    private function validatePromotion(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'in:promotion,announcement'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'is_published' => ['required', 'boolean'],
        ]);
    }
}
