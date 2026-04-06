<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SmsService;

class SmsController extends Controller
{
    //
    public function sendSms(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
        ]);

        $semaphoreService = new SmsService();
        $success = $semaphoreService->send($validated['number'], $validated['message']);

        if ($success) {
            return response()->json(['message' => 'SMS sent successfully']);
        } else {
            return response()->json(['message' => 'Failed to send SMS'], 500);
        }
    }
}
