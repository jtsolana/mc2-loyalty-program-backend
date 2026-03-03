<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'The provided current password is incorrect.',
                'errors' => ['current_password' => ['The provided current password is incorrect.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
