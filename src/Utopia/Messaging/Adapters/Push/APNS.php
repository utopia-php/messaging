<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;
use Utopia\Messaging\Response;

class APNS extends PushAdapter
{
    /**
     * @param  string  $authKey
     * @param  string  $authKeyId
     * @param  string  $teamId
     * @param  string  $bundleId
     * @param  string  $endpoint
     * @return void
     */
    public function __construct(
      private string $authKey,
      private string $authKeyId,
      private string $teamId,
      private string $bundleId,
      private string $endpoint
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'APNS';
    }

    /**
     * Get max messages per request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     *
     * @param  Push  $message
     * @return string
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

        $mh = curl_multi_init();
        $handles = [];

        // Create a handle for each request
        foreach ($to as $token) {
            curl_setopt($ch, CURLOPT_URL, $this->endpoint.'/3/device/'.$token);

            $handle = curl_copy_handle($ch);
            curl_multi_add_handle($mh, $handle);

            $handles[] = $handle;
        }

        $active = 1;
        $status = CURLM_OK;

        // Execute the handles
        while ($active && $status == CURLM_OK) {
            $status = curl_multi_exec($mh, $active);
        }

        $response = new Response(0, 0, $this->getType(), []);

        // Check each handle's result
        foreach ($handles as $ch) {
            $urlInfo = curl_getinfo($ch);
            $result = curl_multi_getcontent($ch);
            
            // Separate headers and body
            list($headerString, $body) = explode("\r\n\r\n", $result, 2);
            $body = \json_decode($body, true);
            $errorMessage = $body ? $body['reason'] : '';
            $device = basename($urlInfo['url']); // Extracts deviceToken from the URL
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $details = $response->getDetails();

            if ($httpCode === 200) {
                $success = $response->getSuccess();

                $success++;
                $details[] = [
                    'recipient' => $device,
                    'status' => 'success',
                    'error' => '',
                ];

                $response->setSuccess($success);
                $response->setDetails($details);
            } else {
                $failure = $response->getFailure();

                $failure++;
                $details[] = [
                    'recipient' => $device,
                    'status' => 'failure',
                    'error' => match ($errorMessage) {
                        'MissingDeviceToken' => 'Bad Request. Missing token.',
                        'BadDeviceToken' => 'Invalid token.',
                        'ExpiredToken' => 'Expired token.',
                        'PayloadTooLarge' => 'Payload is too large. Please keep maximum 4096 bytes for messages.',
                        'TooManyRequests' => 'Too many requests were made consecutively to the same device token.',
                        'InternalServerError' => 'Internal server error.',
                        'PayloadEmpty' => 'Bad Request.',
                        default => $errorMessage,
                    }
                ];

                $response->setFailure($failure);
                $response->setDetails($details);

                if ($httpCode === 401) {
                    $details[\count($response->getDetails())-1]['error'] = 'Authentication error.';

                    $response->setDetails($details);
                }
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        curl_share_close($sh);
        return $response->toArray();
    }


    /**
     * Generate JWT.
     *
     * @return string
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
