<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SemaphoreService
{
    public function sendSms(string $number, string $message)
    {
        return Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
            'apikey' => env('SEMAPHORE_API_KEY'),
            'number' => $number,
            'message' => $message,
            'sendername' => env('SEMAPHORE_SENDER_NAME'),
        ]);
    }
}