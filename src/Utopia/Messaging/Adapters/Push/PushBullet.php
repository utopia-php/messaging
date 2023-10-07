<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;
use GuzzleHttp\Client;

class PushBullet extends PushAdapter
{
    /**
     * @param  string  $apiKey The PushBullet API key.
     */
    public function __construct(string $apiKey)
    {
        private string $apiKey;
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'PushBullet';
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

    public function process(Push $message): bool
    {
        // Compose the Pushbullet API request
        $payload = [
            'type' => 'note',
            'title' => $message->getTitle(),
            'body' => $message->getBody(),
        ];

        try {
            $client = new Client();
            $response = $client->request('POST', 'https://api.pushbullet.com/v2/pushes', [
                'headers' => [
                    'Access-Token' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }
}
