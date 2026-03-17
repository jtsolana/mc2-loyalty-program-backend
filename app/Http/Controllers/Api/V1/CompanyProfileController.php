<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CompanyProfileController extends Controller
{
    public function terms(): JsonResponse
    {
        return Cache::rememberForever('company_terms', function () {
            return response()->json(['data' => CompanyProfile::getSingleton()->terms]);
        });
    }
}
