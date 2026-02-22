<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customerRole = Role::where('name', 'customer')->first();

        $customers = User::query()
            ->when($customerRole, fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('roles.id', $customerRole->id)))
            ->with('loyaltyPoint')
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
            ->paginate(20);

        return response()->json([
            'data' => UserResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles.permissions', 'loyaltyPoint', 'pointTransactions');

        return response()->json(['data' => new UserResource($user)]);
    }
}
