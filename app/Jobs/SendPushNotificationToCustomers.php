<?php

namespace App\Jobs;

use App\Models\UserDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendPushNotificationToCustomers implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $body,
        private array $data = [],
        private ?int $userId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messaging = Firebase::messaging();

        if ($this->userId) {
            $userDevices = UserDevice::where('user_id', $this->userId)->get();
        } else {
            $userDevices = UserDevice::all();
        }

        foreach ($userDevices as $device) {
            try {
                $message = CloudMessage::new()
                    ->withToken($device->fcm_token)
                    ->withNotification(
                        Notification::create(
                            $this->title,
                            $this->body
                        )
                    )
                    ->withData($this->data);

                $messaging->send($message);
            } catch (NotFound $e) {
                \Sentry\captureException($e);
            }
        }
    }
}
