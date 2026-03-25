<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\ContactSupportRequest;
use App\Http\Requests\Api\Customer\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Mail\ContactSupportMail;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'roles.permissions',
            'loyaltyPoint',
        ]);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'data' => new UserResource($request->user()->fresh()->load('roles.permissions', 'loyaltyPoint')),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->devices()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }

    public function contactSupport(ContactSupportRequest $request): JsonResponse
    {
        $companyEmail = CompanyProfile::getSingleton()->email;

        Mail::to($companyEmail)->send(new ContactSupportMail(
            user: $request->user(),
            userSubject: $request->validated('subject'),
            userMessage: $request->validated('message'),
        ));

        return response()->json(['message' => 'Your message has been sent to our support team. We will get back to you shortly.']);
    }
}
