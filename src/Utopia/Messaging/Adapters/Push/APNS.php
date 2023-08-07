<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

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
        return 1000;
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
        $headers = [
            'authorization: bearer '.$this->generateJwt(),
            'apns-topic: '.$this->bundleId,
        ];

        $payload = json_encode([
            'aps' => [
                'alert' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                ],
                'badge' => $message->getBadge(),
                'sound' => $message->getSound(),
                'data' => $message->getData(),
            ],
        ]);

        // Assuming the 'to' array contains device tokens for the push notification recipients.
        foreach ($message->getTo() as $to) {
            $url = $this->endpoint.'/3/device/'.$to;
            $response = $this->request('POST', $url, $headers, $payload);

            // You might want to handle each response here, for instance, logging failures
        }

        // This example simply returns the last response, adjust as needed
        return $response;
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

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlClaims = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claims));

        $privateKeyResource = openssl_pkey_get_private(file_get_contents($this->authKey));
        if (! $privateKeyResource) {
            throw new \Exception('Invalid private key');
        }

        $signature = '';
        $success = openssl_sign("$base64UrlHeader.$base64UrlClaims", $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (! $success) {
            throw new \Exception('Failed to sign JWT');
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "$base64UrlHeader.$base64UrlClaims.$base64UrlSignature";
    }
}
