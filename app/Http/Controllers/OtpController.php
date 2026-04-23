<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\SemaphoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OtpController extends Controller
{
    //
    protected $semaphoreService;

    public function __construct(SemaphoreService $semaphoreService)
    {
        $this->semaphoreService = $semaphoreService;
    }

    public function showPhoneForm()
    {
        return view('auth.phone-verify');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'digits:11'],
        ]);

        $otp = rand(100000, 999999);

        OtpVerification::updateOrCreate(
            ['phone' => $request->phone],
            [
                'user_id' => auth()->id(),
                'otp_code' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
                'is_verified' => false,
            ]
        );

        $message = "Your OTP code is {$otp}. It will expire in 5 minutes.";

        $this->semaphoreService->sendSms($request->phone, $message);

        return redirect()->route('otp.form', ['phone' => $request->phone])
            ->with('success', 'OTP has been sent.');
    }

    public function showOtpForm(Request $request)
    {
        return view('auth.otp-check', [
            'phone' => $request->phone
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'digits:11'],
            'otp_code' => ['required', 'digits:6'],
        ]);

        $otpRecord = OtpVerification::where('phone', $request->phone)
            ->where('otp_code', $request->otp_code)
            ->where('is_verified', false)
            ->first();

        if (!$otpRecord) {
            return back()->withErrors([
                'otp_code' => 'Invalid OTP code.'
            ]);
        }

        if (now()->gt($otpRecord->expires_at)) {
            return back()->withErrors([
                'otp_code' => 'OTP has expired.'
            ]);
        }

        $otpRecord->update([
            'is_verified' => true,
        ]);

        if ($otpRecord->user_id) {
            User::where('id', $otpRecord->user_id)->update([
                'phone' => $otpRecord->phone,
                'phone_verified' => true,
            ]);
        }

        return ;
    }
}
