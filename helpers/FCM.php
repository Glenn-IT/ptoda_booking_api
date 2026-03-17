<?php
// helpers/FCM.php
// Firebase Cloud Messaging — send push notifications via HTTP v1 / Legacy API

require_once __DIR__ . '/../config/config.php';

class FCM {

    /**
     * Send a push notification to a single device token.
     *
     * @param string $deviceToken  Recipient FCM device token
     * @param string $title        Notification title
     * @param string $body         Notification body text
     * @param array  $data         Optional key-value data payload
     * @return bool                True on success, false on failure
     */
    public static function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool {
        $payload = [
            'to'           => $deviceToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ],
            'data'         => $data,
        ];

        return self::send($payload);
    }

    /**
     * Send a push notification to multiple device tokens.
     *
     * @param array  $deviceTokens  Array of FCM device tokens
     * @param string $title
     * @param string $body
     * @param array  $data
     * @return bool
     */
    public static function sendToMultiple(array $deviceTokens, string $title, string $body, array $data = []): bool {
        $payload = [
            'registration_ids' => $deviceTokens,
            'notification'     => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ],
            'data'             => $data,
        ];

        return self::send($payload);
    }

    // ─── Internal ─────────────────────────────────────────

    private static function send(array $payload): bool {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => FCM_API_URL,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: key=' . FCM_SERVER_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[FCM] cURL error: $error");
            return false;
        }

        $result = json_decode($response, true);
        if (isset($result['failure']) && $result['failure'] > 0) {
            error_log("[FCM] Partial failure: " . json_encode($result));
        }

        return isset($result['success']) && $result['success'] > 0;
    }
}
