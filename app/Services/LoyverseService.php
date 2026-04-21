<?php

namespace App\Services;

use App\Models\Reward;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoyverseService
{
    private function baseUrl(): string
    {
        return config('services.loyverse.base_url');
    }

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

        //If the ID is provided, it means the loyverse user wants to update an existing record.
        if (! empty($data['id'])) {
            $payload['id'] = $data['id'];
        }

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

            $response = Http::withToken($this->token())
                ->timeout(10)
                ->post($this->baseUrl().'/customers', $payload);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning('Loyverse createCustomer failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (ConnectionException $e) {
            \Sentry\captureException($e);
        }

        return null;
    }

    /**
     * Get items from Loyverse.
     * Returns null if the API token is missing, the request fails, or Loyverse
     * returns an error.
     */
    public function getItems(int $limit = 250): ?array
    {
        if (! $this->token()) {
            return null;
        }

        try {
            $query = [
                'limit' => $limit,
            ];

            $response = Http::withToken($this->token())
                ->timeout(10)
                ->get($this->baseUrl().'/items', $query);

            if ($response->successful()) {

                $items = collect($response->json('items'))->reject(
                    fn (array $item) => $item['category_id'] && ! in_array($item['category_id'], config('app.loyverse_reward_category'))
                )->toArray();

                return $items;
            }
        } catch (ConnectionException $e) {
            \Sentry\captureException($e);
        }

        return null;
    }

    /**
     * Create a receipt in Loyverse for a claimed reward, which helps maintain accurate records in Loyverse
     * and can be useful for reporting and auditing purposes. This should be called whenever a reward is claimed,
     * and it will create a corresponding receipt in Loyverse with the relevant details.
     */
    public function createRewardReceipt(Reward $reward, string $customerId, string $loyverseVariantId, int $claimAmount): ?array
    {
        if (! $this->token()) {
            return null;
        }

        $loyverseStoreId = config('app.loyverse_store_id');
        $paymentTypeId = config('app.loyverse_payment_type_id');

        $payload = [
            'store_id' => $loyverseStoreId,
            'customer_id' => $customerId,
            'source' => 'MC2 APP',
            'note' => "rewards_claim|{$reward->id}",
            'line_items' => [
                [
                    'variant_id' => $loyverseVariantId,
                    'quantity' => $claimAmount,
                    'price' => 0,
                    'cost' => 0,
                ],
            ],
            'payments' => [
                [
                    'payment_type_id' => $paymentTypeId,
                    'money_amount' => 0,
                    'paid_at' => now()->toIso8601String(),
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->token())
                ->timeout(10)
                ->post($this->baseUrl().'/receipts', $payload);

            if ($response->successful()) {
                return $response->json('line_items');
            }
        } catch (ConnectionException $e) {
            \Sentry\captureException($e);
        }

        return null;
    }
}
