<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotificationToCustomers;
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
        $promotions = Promotion::latest()->paginate(10)->through(fn ($promotion) => [
            'id' => $promotion->id,
            'hashed_id' => $promotion->hashed_id,
            'title' => $promotion->title,
            'excerpt' => $promotion->excerpt,
            'thumbnail_url' => $promotion->thumbnail ? Storage::disk('public')->url($promotion->thumbnail) : null,
            'content' => $promotion->content,
            'type' => $promotion->type,
            'publish_status' => $promotion->publish_status,
            'is_published' => $promotion->is_published,
            'published_at' => $promotion->published_at?->toDateTimeString(),
            'expires_at' => $promotion->expires_at?->toDateTimeString(),
            'created_at' => $promotion->created_at?->toDateString(),
        ]);

        return Inertia::render('admin/promotions/index', [
            'promotions' => $promotions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePromotion($request);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('promotions', 'public');
        }

        $promotion = Promotion::create($this->resolvePublishData($data));

        if ($promotion->is_published) {
            $this->dispatchPushNotification($promotion);
        }

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

        $wasPublished = $promotion->is_published;
        $promotion->update($this->resolvePublishData($data));

        if (! $wasPublished && $promotion->fresh()->is_published) {
            $this->dispatchPushNotification($promotion);
        }

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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolvePublishData(array $data): array
    {
        return match ($data['publish_status']) {
            'published' => array_merge($data, [
                'is_published' => true,
                'published_at' => now(),
            ]),
            'scheduled' => array_merge($data, [
                'is_published' => false,
                'published_at' => $data['published_at'],
            ]),
            default => array_merge($data, [
                'is_published' => false,
                'published_at' => null,
            ]),
        };
    }

    private function dispatchPushNotification(Promotion $promotion): void
    {
        $mobileScheme = config('app.mobile_scheme');

        SendPushNotificationToCustomers::dispatch(
            "📣 {$promotion->title}",
            $promotion->excerpt,
            [
                'type' => 'promotion',
                'promotion_id' => (string) $promotion->hashed_id,
                'deep_link' => "{$mobileScheme}promotions/{$promotion->hashed_id}",
            ]
        )->onQueue('loyverse');
    }

    /** @return array<string, mixed> */
    private function validatePromotion(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'type' => [
                'required',
                'string',
                'in:popup-promotion,promotion,announcement',
            ],
            'publish_status' => ['required', 'string', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date', 'required_if:publish_status,scheduled', 'after:now'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
        ]);
    }
}
