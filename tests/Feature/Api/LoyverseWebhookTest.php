<?php

use App\Jobs\ProcessLoyverseReceipt;
use App\Models\LoyaltyPoint;
use App\Models\PointRule;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use App\Services\PointService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeWebhookPayload(string $receiptId, float $totalMoney, ?string $customerId = null, int $quantity = 0): array
{
    return [
        'type' => 'receipts.update',
        'receipts' => [
            [
                'receipt_number' => $receiptId,
                'receipt_type' => 'SALE',
                'total_money' => $totalMoney,
                'customer_id' => $customerId,
                'line_items' => $quantity > 0
                    ? [['quantity' => $quantity, 'item_name' => 'Drink', 'price' => $totalMoney]]
                    : [],
            ],
        ],
    ];
}

function makeSaleReceipt(string $receiptId, float $totalMoney, ?string $customerId = null, int $quantity = 0): array
{
    return [
        'receipt_number' => $receiptId,
        'receipt_type' => 'SALE',
        'total_money' => $totalMoney,
        'customer_id' => $customerId,
        'line_items' => $quantity > 0
            ? [['quantity' => $quantity, 'item_name' => 'Drink', 'price' => $totalMoney]]
            : [],
    ];
}

// --- Controller tests: assert job is dispatched ---

it('dispatches a job for each receipt in the payload', function () {
    Queue::fake();

    $receiptId = 'R-'.Str::random(8);

    $this->postJson('/api/v1/loyverse/webhook', makeWebhookPayload($receiptId, 500))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Webhook processed successfully.');

    Queue::assertPushedOn('loyverse', ProcessLoyverseReceipt::class);
    Queue::assertPushed(ProcessLoyverseReceipt::class, 1);
});

it('dispatches one job per receipt when multiple receipts are sent', function () {
    Queue::fake();

    $this->postJson('/api/v1/loyverse/webhook', [
        'type' => 'receipts.update',
        'receipts' => [
            makeSaleReceipt('R-001', 100),
            makeSaleReceipt('R-002', 200),
            makeSaleReceipt('R-003', 300),
        ],
    ])->assertSuccessful();

    Queue::assertPushed(ProcessLoyverseReceipt::class, 3);
});

it('does not dispatch any jobs for unhandled event types', function () {
    Queue::fake();

    $this->postJson('/api/v1/loyverse/webhook', ['type' => 'customers.update'])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Event type not handled.');

    Queue::assertNothingPushed();
});

// --- Job tests: verify processing logic ---

it('job creates a purchase record for a SALE receipt', function () {
    $receiptId = 'R-'.Str::random(8);
    $receipt = makeSaleReceipt($receiptId, 500);

    (new ProcessLoyverseReceipt($receipt))->handle(app(PointService::class));

    $this->assertDatabaseHas('purchases', ['loyverse_receipt_id' => $receiptId, 'total_amount' => 500]);
});

it('job awards points to a matched customer', function () {
    PointRule::factory()->create(['spend_amount' => 50, 'points_per_unit' => 1, 'is_active' => true]);
    $customer = User::factory()->create(['loyverse_customer_id' => 'LV-CUST-001']);
    $customer->roles()->attach(Role::where('name', 'customer')->first());
    LoyaltyPoint::factory()->create(['user_id' => $customer->id, 'total_points' => 0]);

    $receiptId = 'R-'.Str::random(8);
    $receipt = makeSaleReceipt($receiptId, 500, 'LV-CUST-001');

    (new ProcessLoyverseReceipt($receipt))->handle(app(PointService::class));

    expect($customer->fresh()->loyaltyPoint->total_points)->toBe(10);
    $this->assertDatabaseHas('purchases', ['loyverse_receipt_id' => $receiptId, 'points_earned' => 10]);
});

it('job does not award points when no active rule exists', function () {
    $customer = User::factory()->create(['loyverse_customer_id' => 'LV-CUST-002']);
    LoyaltyPoint::factory()->create(['user_id' => $customer->id, 'total_points' => 0]);

    $receipt = makeSaleReceipt('R-NORULE', 100, 'LV-CUST-002');

    (new ProcessLoyverseReceipt($receipt))->handle(app(PointService::class));

    $this->assertDatabaseHas('purchases', ['loyverse_receipt_id' => 'R-NORULE', 'points_earned' => 0]);
});

it('job skips duplicate receipts', function () {
    $receiptId = 'R-DUPE';
    Purchase::factory()->create(['loyverse_receipt_id' => $receiptId]);

    $receipt = makeSaleReceipt($receiptId, 200);

    (new ProcessLoyverseReceipt($receipt))->handle(app(PointService::class));

    expect(Purchase::where('loyverse_receipt_id', $receiptId)->count())->toBe(1);
});

it('job skips non-SALE receipts', function () {
    $receipt = [
        'receipt_number' => 'R-REFUND',
        'receipt_type' => 'REFUND',
        'total_money' => 300,
        'customer_id' => null,
        'line_items' => [],
    ];

    (new ProcessLoyverseReceipt($receipt))->handle(app(PointService::class));

    $this->assertDatabaseMissing('purchases', ['loyverse_receipt_id' => 'R-REFUND']);
});
