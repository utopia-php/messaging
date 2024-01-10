<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Helpers\JWT;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Response;

class APNS extends PushAdapter
{
    private const NAME = 'APNS';

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
        return self::NAME;
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
     */
    public function process(PushMessage $message): array
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

        $claims = [
            'iss' => $this->teamId,   // Issuer
            'iat' => \time(),         // Issued at time
            'exp' => \time() + 3600,  // Expiration time
        ];

        $jwt = JWT::encode(
            $claims,
            $this->authKey,
            'ES256',
            $this->authKeyId
        );

        $endpoint = 'https://api.push.apple.com';

        if ($this->sandbox) {
            $endpoint = 'https://api.development.push.apple.com';
        }

        $urls = [];
        foreach ($message->getTo() as $token) {
            $urls[] = $endpoint.'/3/device/'.$token;
        }

        $results = $this->requestMulti(
            method: 'POST',
            urls: $urls,
            headers: [
                'Authorization: Bearer '.$jwt,
                'apns-topic: '.$this->bundleId,
                'apns-push-type: alert',
            ],
            bodies: [\json_encode($payload)]
        );

        $response = new Response($this->getType());

        foreach ($results as $result) {
            $device = \basename($result['url']);
            $statusCode = $result['statusCode'];

            switch ($statusCode) {
                case 200:
                    $response->incrementDeliveredTo();
                    $response->addResultForRecipient($device);
                    break;
                default:
                    $response->addResultForRecipient(
                        $device,
                        $result['response']['reason'] === 'ExpiredToken' ||
                        $result['response']['reason'] === 'BadDeviceToken'
                            ? $this->getExpiredErrorMessage()
                            : $result['response']['reason'],
                    );
                    break;
            }
        }

        return $response->toArray();
    }
}
