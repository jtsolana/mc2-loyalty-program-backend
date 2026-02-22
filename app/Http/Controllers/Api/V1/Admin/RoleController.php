<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\RolePermissionRequest;
use App\Http\Resources\Api\PermissionResource;
use App\Http\Resources\Api\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => RoleResource::collection(Role::with('permissions')->get()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'alpha_dash'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($request->only('name', 'display_name', 'description'));

        return response()->json(['data' => new RoleResource($role)], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(['data' => new RoleResource($role->load('permissions'))]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'display_name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $role->update($request->only('display_name', 'description'));

        return response()->json(['data' => new RoleResource($role->load('permissions'))]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $protected = ['admin', 'staff', 'customer'];

        if (in_array($role->name, $protected)) {
            return response()->json(['message' => 'Cannot delete a built-in role.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    public function permissions(Role $role): JsonResponse
    {
        return response()->json([
            'data' => [
                'role' => new RoleResource($role),
                'assigned' => PermissionResource::collection($role->permissions),
                'available' => PermissionResource::collection(Permission::all()),
            ],
        ]);
    }

    public function syncPermissions(RolePermissionRequest $request, Role $role): JsonResponse
    {
        $role->permissions()->sync($request->input('permissions'));

        return response()->json([
            'data' => new RoleResource($role->load('permissions')),
            'message' => 'Permissions updated successfully.',
        ]);
    }
}
