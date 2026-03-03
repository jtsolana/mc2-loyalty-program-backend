<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserDevice;

class UserDeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'platform' => 'required|in:android,ios',
        ]);
    
        $user = $request->user();
    
        UserDevice::updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            [
                'user_id' => $user->id,
                'platform' => $request->platform,
            ]
        );
    
        return response()->json(['message' => 'Device registered']);
    }

    public function unregister(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        UserDevice::where('fcm_token', $request->fcm_token)->delete();

        return response()->json(['message' => 'Device unregistered successfully']);
    }
}
