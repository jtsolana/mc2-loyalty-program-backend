<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class SendPushNotificationToAllCustomer implements ShouldQueue
{
    use Queueable;

    private string $title;
    private string $body;
    private array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messaging = Firebase::messaging();

        $userDevices = UserDevice::all();

        foreach ($userDevices as $device) {

            $message = CloudMessage::new()
                ->withToken($device->fcm_token)
                ->withNotification(
                    Notification::create(
                        $this->title,
                        $this->body
                    )
                )
                ->withData($this->data);

            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                Log::warning($e->getMessage(), ['token' => $device->fcm_token]);
            }
        }
    }
}
