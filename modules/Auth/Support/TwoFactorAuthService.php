<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use RuntimeException;

/**
 * RFC 6238 TOTP implementation compatible with Google Authenticator and FreeOTP.
 */
final class TwoFactorAuthService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const TIME_STEP = 30;
    private const DIGITS = 6;

    /**
     * @param int $tolerance clock skew tolerance in time steps
     */
    public function __construct(
        private readonly int $step = self::TIME_STEP,
        private readonly int $digits = self::DIGITS,
        private readonly int $tolerance = 1,
    ) {
        if ($this->step < 1 || $this->digits < 6 || $this->digits > 10 || $this->tolerance < 0) {
            throw new RuntimeException('Invalid TOTP configuration.');
        }
    }

    public function generateSecret(int $length = 20): string
    {
        if ($length < 16) {
            $length = 16;
        }

        return $this->encodeBase32(random_bytes($length));
    }

    public function verify(string $secret, string $code, ?int $at = null): bool
    {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return false;
        }

        $counter = intdiv($at ?? time(), $this->step);

        for ($offset = -$this->tolerance; $offset <= $this->tolerance; $offset++) {
            if (hash_equals($this->codeAtStep($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function currentCode(string $secret): string
    {
        return $this->codeAtStep($secret, intdiv(time(), $this->step));
    }

    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $accountName = trim($accountName);
        $issuer = trim($issuer);
        $label = $issuer !== '' ? $issuer . ':' . $accountName : $accountName;

        return 'otpauth://totp/' . rawurlencode($label)
            . '?secret=' . rawurlencode(strtoupper($this->normalizeSecretForUri($secret)))
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1'
            . '&digits=' . $this->digits
            . '&period=' . $this->step;
    }

    public function codeAtStep(string $secret, int $counter): string
    {
        $key = $this->decodeBase32($secret);
        $message = pack('J', $counter);
        $hash = hash_hmac('sha1', $message, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $binary = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        $modulo = 10 ** $this->digits;

        return str_pad((string) ($binary % $modulo), $this->digits, '0', STR_PAD_LEFT);
    }

    public function encodeBase32(string $bytes): string
    {
        $bits = '';

        for ($i = 0, $length = strlen($bytes); $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        for ($i = 0, $bitsLength = strlen($bits); $i + 5 <= $bitsLength; $i += 5) {
            $encoded .= self::BASE32_ALPHABET[bindec(substr($bits, $i, 5))];
        }

        return $encoded;
    }

    public function decodeBase32(string $input): string
    {
        $input = strtoupper((string) preg_replace('/[^A-Za-z2-7]/', '', trim($input)));
        $bits = '';

        for ($i = 0, $length = strlen($input); $i < $length; $i++) {
            $index = strpos(self::BASE32_ALPHABET, $input[$i]);

            if ($index === false) {
                throw new RuntimeException('Invalid Base32 character in two-factor secret.');
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        for ($i = 0, $bitsLength = strlen($bits); $i + 8 <= $bitsLength; $i += 8) {
            $decoded .= chr(bindec(substr($bits, $i, 8)));
        }

        return $decoded;
    }

    private function normalizeCode(string $code): string
    {
        $code = (string) preg_replace('/\s+/', '', trim($code));

        return preg_match('/^\d{6,10}$/', $code) === 1 ? $code : '';
    }

    private function normalizeSecretForUri(string $secret): string
    {
        return (string) preg_replace('/[^A-Za-z2-7]/', '', trim($secret));
    }
}