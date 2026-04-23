<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\OtpVerification;
use App\Services\SemaphoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified = false;

            OtpVerification::where('user_id', $user->id)
                ->where('is_verified', false)
                ->delete();
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function sendPhoneOtp(Request $request, SemaphoreService $semaphoreService): RedirectResponse
    {
        $user = $request->user();

        if (!$user->phone) {
            return Redirect::route('profile.edit')->withErrors([
                'phone' => 'Please save your phone number first.',
            ]);
        }

        if ($user->phone_verified) {
            return Redirect::route('profile.edit')->with('status', 'phone-already-verified');
        }

        $lastOtp = OtpVerification::where('user_id', $user->id)
            ->where('phone', $user->phone)
            ->where('is_verified', false)
            ->latest('updated_at')
            ->first();

        $cooldownSeconds = 60;

        if ($lastOtp && $lastOtp->updated_at->diffInSeconds(now()) < $cooldownSeconds) {
            $secondsLeft = $cooldownSeconds - $lastOtp->updated_at->diffInSeconds(now());

            return Redirect::route('profile.edit')->withErrors([
                'phone' => "Please wait {$secondsLeft} seconds before requesting another OTP.",
            ]);
        }

        $otpCode = (string) random_int(100000, 999999);

        OtpVerification::updateOrCreate(
            [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ],
            [
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
                'is_verified' => false,
            ]
        );

        $message = "Your OTP code is {$otpCode}. It will expire in 5 minutes.";

        try {
            $response = $semaphoreService->sendSms($user->phone, $message);
        } catch (\Throwable) {
            return Redirect::route('profile.edit')->withErrors([
                'phone' => 'Failed to send OTP. Please try again.',
            ]);
        }

        if (!$response->successful()) {
            return Redirect::route('profile.edit')->withErrors([
                'phone' => 'OTP was not sent. Please try again.',
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'phone-otp-sent');
    }

    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (!$user->phone) {
            return Redirect::route('profile.edit')->withErrors([
                'phone' => 'Please save your phone number first.',
            ]);
        }

        if ($user->phone_verified) {
            return Redirect::route('profile.edit')->with('status', 'phone-already-verified');
        }

        $otpRecord = OtpVerification::where('user_id', $user->id)
            ->where('phone', $user->phone)
            ->where('otp_code', $request->input('otp_code'))
            ->where('is_verified', false)
            ->latest('updated_at')
            ->first();

        if (!$otpRecord) {
            return Redirect::route('profile.edit')->withErrors([
                'otp_code' => 'Invalid OTP code.',
            ]);
        }

        if (now()->gt($otpRecord->expires_at)) {
            return Redirect::route('profile.edit')->withErrors([
                'otp_code' => 'OTP has expired. Request a new code.',
            ]);
        }

        $otpRecord->update([
            'is_verified' => true,
        ]);

        $user->forceFill([
            'phone_verified' => true,
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'phone-verified');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
