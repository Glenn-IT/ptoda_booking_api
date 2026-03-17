<?php
// helpers/JWT.php
// Minimal JWT encode / decode (HS256) — no external library needed

require_once __DIR__ . '/../config/config.php';

class JWT {

    /**
     * Encode a payload into a JWT token string.
     */
    public static function encode(array $payload): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;

        $header    = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body      = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$body", JWT_SECRET, true)
        );

        return "$header.$body.$signature";
    }

    /**
     * Decode and verify a JWT token string.
     * Returns the payload array on success, or throws Exception on failure.
     */
    public static function decode(string $token): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token structure');
        }

        [$header, $body, $signature] = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$body", JWT_SECRET, true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            throw new Exception('Invalid token signature');
        }

        $payload = json_decode(self::base64UrlDecode($body), true);

        if (!$payload) {
            throw new Exception('Invalid token payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    // ─── Helpers ──────────────────────────────────────────

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
