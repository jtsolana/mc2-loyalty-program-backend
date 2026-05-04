<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly PointService $pointService) {}

    public function index(Request $request): Response
    {
        $customerRole = Role::where('name', 'customer')->first();

        $customers = User::query()
            ->when($customerRole, fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('roles.id', $customerRole->id)))
            ->with('loyaltyPoint')
            ->withCount('purchases')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'hashed_id' => $user->hashed_id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('F j, Y'),
                'avatar' => $user->avatar,
                'purchases_count' => $user->purchases_count,
                'total_points' => $user->loyaltyPoint?->total_points ?? 0,
                'lifetime_points' => $user->loyaltyPoint?->lifetime_points ?? 0,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => ['search' => $request->input('search', '')],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('loyaltyPoint');

        $purchases = $user->purchases()
            ->latest()
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'loyverse_receipt_id', 'total_amount', 'points_earned', 'status', 'created_at', 'loyverse_payload']);

        $transactions = $user->pointTransactions()
            ->latest()
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'type', 'points', 'balance_after', 'description', 'created_at']);

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'id' => $user->id,
                'hashed_id' => $user->hashed_id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('F j, Y'),
                'date_of_birth_iso' => $user->date_of_birth?->toDateString(),
                'avatar' => $user->avatar,
                'total_points' => $user->loyaltyPoint?->total_points ?? 0,
                'lifetime_points' => $user->loyaltyPoint?->lifetime_points ?? 0,
                'created_at' => $user->created_at?->toDateString(),
            ],
            'purchases' => $purchases,
            'transactions' => $transactions,
            'canEditCustomer' => auth()->user()?->hasRole('admin') ?? false,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        $user->update(['date_of_birth' => $data['date_of_birth'] ?? null]);

        return back()->with('success', 'Customer updated successfully.');
    }

    public function creditPoints(Request $request, User $user): RedirectResponse
    {
        $admin = auth()->user();

        abort_unless($admin?->hasRole('admin'), 403);

        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->pointService->adjustPoints($user, $data['points'], $data['reason'], $admin);

        return back()->with('success', "Credited {$data['points']} points to {$user->name}.");
    }
}
