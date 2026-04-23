<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfilePhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_verification_status_resets_when_phone_is_changed(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => true,
        ]);

        OtpVerification::create([
            'user_id' => $user->id,
            'phone' => '09123456789',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '09987654321',
        ])->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('09987654321', $user->phone);
        $this->assertFalse($user->phone_verified);
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_user_can_send_otp_from_profile_for_unverified_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => false,
        ]);

        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 1]], 200),
        ]);

        $this->actingAs($user)
            ->post('/profile/phone/send-otp')
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'phone-otp-sent');

        $this->assertDatabaseHas('otp_verifications', [
            'user_id' => $user->id,
            'phone' => '09123456789',
            'is_verified' => false,
        ]);
    }

    public function test_send_otp_respects_cooldown(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => false,
        ]);

        OtpVerification::create([
            'user_id' => $user->id,
            'phone' => '09123456789',
            'otp_code' => '654321',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        Http::fake();

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile/phone/send-otp')
            ->assertRedirect('/profile')
            ->assertSessionHasErrors(['phone']);

        Http::assertNothingSent();
    }

    public function test_user_can_verify_otp_from_profile(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => false,
        ]);

        $otp = OtpVerification::create([
            'user_id' => $user->id,
            'phone' => '09123456789',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        $this->actingAs($user)
            ->post('/profile/phone/verify-otp', [
                'otp_code' => '123456',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'phone-verified');

        $this->assertTrue($user->fresh()->phone_verified);
        $this->assertTrue($otp->fresh()->is_verified);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => false,
        ]);

        OtpVerification::create([
            'user_id' => $user->id,
            'phone' => '09123456789',
            'otp_code' => '111111',
            'expires_at' => now()->subMinute(),
            'is_verified' => false,
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile/phone/verify-otp', [
                'otp_code' => '111111',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors(['otp_code']);

        $this->assertFalse($user->fresh()->phone_verified);
    }
}
