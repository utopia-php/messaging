<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class APNS extends PushAdapter
{
    /**
     * @return void
     */
    public function __construct(
        private string $authKey,
        private string $authKeyId,
        private string $teamId,
        private string $bundleId,
        private bool $sandbox = false
    ) {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return 'APNS';
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     *
     *
     * @throws Exception
     */
    public function process(Push $message): string
    {
        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                ],
                'badge' => $message->getBadge(),
                'sound' => $message->getSound(),
                'data' => $message->getData(),
            ],
        ];

        return \json_encode($this->notify($message->getTo(), $payload));
    }

    private function notify(array $to, array $payload): array
    {
        $headers = [
            'authorization: bearer '.$this->generateJwt(),
            'apns-topic: '.$this->bundleId,
            'apns-push-type: alert',
        ];

        $sh = curl_share_init();

        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SHARE, $sh);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        curl_setopt_array($ch, [
            CURLOPT_PORT => 443,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
        ]);

        $response = '';
        $endpoint = 'https://api.push.apple.com';

        if ($this->sandbox) {
            $endpoint = 'https://api.development.push.apple.com';
        }

        $mh = curl_multi_init();
        $handles = [];

        // Create a handle for each request
        foreach ($to as $token) {
            curl_setopt($ch, CURLOPT_URL, $endpoint.'/3/device/'.$token);

            $handle = curl_copy_handle($ch);
            curl_multi_add_handle($mh, $handle);

            $handles[] = $handle;
        }

        $active = null;
        $status = CURLM_OK;

        // Execute the handles
        while ($active && $status == CURLM_OK) {
            $status = curl_multi_exec($mh, $active);
        }

        // Check each handle's result
        $responses = [];
        foreach ($handles as $ch) {
            $urlInfo = curl_getinfo($ch);
            $device = basename($urlInfo['url']); // Extracts deviceToken from the URL

            if (! curl_errno($ch)) {
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $responses[] = [
                    'device' => $device,
                    'status' => 'success',
                    'statusCode' => $statusCode,
                ];
            } else {
                $responses[$device] = [
                    'status' => 'error',
                    'error' => curl_error($ch),
                ];
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        curl_share_close($sh);

        return $responses;
    }

    private function formatResponse(string $response): array
    {
        $filtered = array_filter(
            explode("\r\n", $response),
            function ($value) {
                return ! empty($value);
            }
        );

        $result = [];

        foreach ($filtered as $value) {
            if (str_contains($value, 'HTTP')) {
                $result['status'] = trim(str_replace('HTTP/2 ', '', $value));

                continue;
            }

            $parts = explode(':', trim($value));

            $result[$parts[0]] = $parts[1];
        }

        return $result;
    }

    /**
     * Generate JWT.
     *
     *
     * @throws Exception
     */
    private function generateJwt(): string
    {
        $header = json_encode(['alg' => 'ES256', 'kid' => $this->authKeyId]);
        $claims = json_encode([
            'iss' => $this->teamId,
            'iat' => time(),
        ]);

        // Replaces URL sensitive characters that could be the result of base64 encoding.
        // Replace to _ to avoid any special handling.
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlClaims = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claims));

        if (! $this->authKey) {
            throw new \Exception('Invalid private key');
        }

        $signature = '';
        $success = openssl_sign("$base64UrlHeader.$base64UrlClaims", $signature, $this->authKey, OPENSSL_ALGO_SHA256);

        if (! $success) {
            throw new \Exception('Failed to sign JWT');
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "$base64UrlHeader.$base64UrlClaims.$base64UrlSignature";
    }
}
