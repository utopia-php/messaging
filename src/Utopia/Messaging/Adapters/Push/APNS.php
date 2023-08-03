<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class APNS extends PushAdapter
{
    private $authKey;
    private $authKeyId;
    private $teamId;
    private $bundleId;
    private $endpoint;

    public function __construct(string $authKey, string $authKeyId, string $teamId, string $bundleId, string $endpoint)
    {
        $this->authKey = $authKey;
        $this->authKeyId = $authKeyId;
        $this->teamId = $teamId;
        $this->bundleId = $bundleId;
        $this->endpoint = $endpoint;
    }

    public function getName(): string
    {
        return 'APNS';
    }


    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    public function process(Push $message): string
    {
        $headers = [
            'authorization: bearer ' . $this->generateJwt(),
            'apns-topic: ' . $this->bundleId,
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
            $url = $this->endpoint . '/3/device/' . $to;
            $response = $this->request('POST', $url, $headers, $payload);
 
            // You might want to handle each response here, for instance, logging failures
        }

        // This example simply returns the last response, adjust as needed
        return $response;
    }
    
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
        if (!$privateKeyResource) {
            throw new \Exception('Invalid private key');
        }
    
        $signature = '';
        $success = openssl_sign("$base64UrlHeader.$base64UrlClaims", $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
    
        if (!$success) {
            throw new \Exception('Failed to sign JWT');
        }
    
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
        return "$base64UrlHeader.$base64UrlClaims.$base64UrlSignature";
    }
    
}
