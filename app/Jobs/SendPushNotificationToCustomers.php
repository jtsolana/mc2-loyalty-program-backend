<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\UserDevice;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;

class SendPushNotificationToCustomers implements ShouldQueue
{
    use Queueable;

    private string $title;
    private string $body;
    private array $data;
    private ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, string $body, array $data = [], ?int $userId = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messaging = Firebase::messaging();

        if($this->userId) {
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
                            $this->body,
                            url('favicon/web-app-manifest-192x192.png')
                        )
                    )
                    ->withData($this->data);

                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                Log::warning($e->getMessage(), ['token' => $device->fcm_token]);
            }
        }
    }
}
