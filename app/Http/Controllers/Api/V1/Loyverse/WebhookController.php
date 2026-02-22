<?php

namespace App\Http\Controllers\Api\V1\Loyverse;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLoyverseReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = $payload['type'] ?? null;

        if ($type !== 'receipts.update') {
            return response()->json(['message' => 'Event type not handled.']);
        }

        $receipts = $payload['receipts'] ?? [];

        foreach ($receipts as $receipt) {
            Log::info('Processing receipt', ['receipt' => $receipt]);
            ProcessLoyverseReceipt::dispatch($receipt)->onQueue('loyverse');
        }

        return response()->json(['message' => 'Webhook processed successfully.']);
    }
}
