<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $sender;

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.key');
        $this->sender = config('services.semaphore.sender');
    }

    public function send($number, $message)
    {
        try {
            $payload = [
                'apikey' => $this->apiKey,
                'number' => $number,
                'message' => $message,
            ];

            if (!empty($this->sender)) {
                $payload['sendername'] = $this->sender;
            }

            $response = Http::asForm()->post(
                'https://semaphore.co/api/v4/messages',
                $payload
            );

            if ($response->successful()) {
                Log::info('SMS sent', [
                    'number' => $number,
                    'message' => $message
                ]);
                return true;
            }

            Log::error('SMS failed', [
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS exception', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
