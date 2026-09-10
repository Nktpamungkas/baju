<?php

namespace App\Domains\Order\Repository;

use App\Models\OtpVerification;

class OtpRepository
{
    private const EXPIRY_MINUTES = 5;
    private const VERIFIED_WINDOW_MINUTES = 30; // cukup buat nyelesain satu sesi checkout

    public function generate(string $phone): string
    {
        $code = (string) random_int(100000, 999999);

        OtpVerification::updateOrCreate(
            ['phone' => $phone],
            ['code' => $code, 'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES), 'verified_at' => null]
        );

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpVerification::where('phone', $phone)->first();

        if (! $otp || ! hash_equals($otp->code, $code) || $otp->expires_at->isPast()) {
            return false;
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }

    public function isRecentlyVerified(string $phone): bool
    {
        return OtpVerification::where('phone', $phone)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(self::VERIFIED_WINDOW_MINUTES))
            ->exists();
    }
}
