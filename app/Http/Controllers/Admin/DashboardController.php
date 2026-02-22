<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Purchase;
use App\Models\Redemption;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $customerRole = Role::where('name', 'customer')->first();

        $stats = [
            'total_customers' => $customerRole
                ? User::whereHas('roles', fn ($q) => $q->where('roles.id', $customerRole->id))->count()
                : 0,
            'total_purchases' => Purchase::count(),
            'total_points_issued' => (int) LoyaltyPoint::sum('lifetime_points'),
            'total_redemptions' => Redemption::count(),
        ];

        $recentCustomers = User::query()
            ->when($customerRole, fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('roles.id', $customerRole->id)))
            ->with('loyaltyPoint')
            ->withCount('purchases')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'hashed_id' => $user->hashed_id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'purchases_count' => $user->purchases_count,
                'total_points' => $user->loyaltyPoint?->total_points ?? 0,
                'lifetime_points' => $user->loyaltyPoint?->lifetime_points ?? 0,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        $monthlyPurchases = Purchase::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(total_amount) as revenue, SUM(points_earned) as points')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recentCustomers' => $recentCustomers,
            'monthlyPurchases' => $monthlyPurchases,
        ]);
    }
}
