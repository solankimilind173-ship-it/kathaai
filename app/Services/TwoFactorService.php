<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function generateSecret(): string
    {
        $google2fa = new Google2FA;
        return $google2fa->generateSecretKey(32);
    }

    public function getQRCodeSvg(User $user, string $secret): string
    {
        $google2fa = new Google2FA;
        $companyName = config('app.name');
        $qrCodeUrl = $google2fa->getQRCodeUrl($companyName, $user->email, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $google2fa = new Google2FA;
        return $google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }

    public function enableTwoFactor(User $user, string $secret, string $code, array $recoveryCodes): bool
    {
        if (!$this->verifyCode($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return true;
    }

    public function disableTwoFactor(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function confirmTwoFactor(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null;
        if (!$secret) {
            return false;
        }
        if ($this->verifyCode($secret, $code)) {
            return true;
        }
        $recoveryCodes = $user->two_factor_recovery_codes ? json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) : [];
        if (is_array($recoveryCodes) && in_array($code, $recoveryCodes, true)) {
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->forceFill([
                'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            ])->save();
            return true;
        }
        return false;
    }

    public function hasEnabledTwoFactor(User $user): bool
    {
        return !empty($user->two_factor_confirmed_at) && !empty($user->two_factor_secret);
    }
}
