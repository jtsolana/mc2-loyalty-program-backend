<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Reward;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : now()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->endOfDay()
            : now()->endOfDay();

        $customerRole = Role::where('name', 'customer')->first();

        $stats = [
            'total_customers' => $customerRole
                ? User::whereHas('roles', fn ($q) => $q->where('roles.id', $customerRole->id))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count()
                : 0,
            'total_purchases' => Purchase::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_points_issued' => (int) Purchase::whereBetween('created_at', [$startDate, $endDate])->sum('points_earned'),
            'total_redemptions' => Reward::where('status', 'claimed')->whereBetween('created_at', [$startDate, $endDate])->count(),
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

        $dateFormat = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d')";

        $monthlyPurchases = Purchase::query()
            ->selectRaw("{$dateFormat} as month, COUNT(*) as count, SUM(total_amount) as revenue, SUM(points_earned) as points")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recentCustomers' => $recentCustomers,
            'monthlyPurchases' => $monthlyPurchases,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }
}
