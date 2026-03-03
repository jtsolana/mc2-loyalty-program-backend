<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;

class CompanyProfileController extends Controller
{
    public function terms(): JsonResponse
    {
        return response()->json(['data' => CompanyProfile::getSingleton()->terms]);
    }
}
