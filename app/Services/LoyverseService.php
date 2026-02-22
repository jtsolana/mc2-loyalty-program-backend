<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoyverseService
{
    private function token(): ?string
    {
        return config('services.loyverse.api_token');
    }

    /**
     * Create a customer in Loyverse and return their Loyverse customer ID.
     * Returns null if the API token is missing, the request fails, or Loyverse
     * returns an error — registration should never be blocked by this call.
     *
     * @param  array{name: string, email?: string|null, phone?: string|null, customer_code?: string|null}  $data
     */
    public function createCustomer(array $data): ?string
    {
        if (! $this->token()) {
            return null;
        }

        $payload = ['name' => $data['name']];

        if (! empty($data['email'])) {
            $payload['email'] = $data['email'];
        }

        if (! empty($data['phone'])) {
            $payload['phone_numbers'] = [$data['phone']];
        }

        if (! empty($data['customer_code'])) {
            $payload['customer_code'] = $data['customer_code'];
        }

        try {

            $baseUrl = config('services.loyverse.base_url');

            $response = Http::withToken($this->token())
                ->timeout(10)
                ->post($baseUrl.'/customers', $payload);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning('Loyverse createCustomer failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (ConnectionException $e) {
            Log::error('Loyverse API unreachable during createCustomer', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
