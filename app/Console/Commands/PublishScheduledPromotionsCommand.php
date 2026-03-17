<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotificationToCustomers;
use App\Models\Promotion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PublishScheduledPromotionsCommand extends Command
{
    protected $signature = 'promotions:publish-scheduled';

    protected $description = 'Publish scheduled promotions whose published_at time has arrived';

    public function handle(): void
    {
        $promotions = Promotion::query()
            ->where('publish_status', 'scheduled')
            ->where('is_published', false)
            ->where('published_at', '<=', now())
            ->get();

        foreach ($promotions as $promotion) {
            $promotion->update([
                'publish_status' => 'published',
                'is_published' => true,
            ]);

            $mobileScheme = config('app.mobile_scheme');

            SendPushNotificationToCustomers::dispatch(
                "📣 {$promotion->title}",
                $promotion->excerpt,
                [
                    'type' => 'promotion',
                    'promotion_id' => (string) $promotion->hashed_id,
                    'deep_link' => "{$mobileScheme}promotions/{$promotion->hashed_id}",
                ]
            )->onQueue('loyverse');
        }

        if ($promotions->isNotEmpty()) {
            Cache::increment('promotions:version');
        }

        $this->info("Published {$promotions->count()} scheduled promotion(s).");
    }
}
