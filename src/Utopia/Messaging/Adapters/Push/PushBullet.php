<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use GuzzleHttp\Client;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class PushBullet extends PushAdapter
{
    /**
     * @param  string  $apiKey The PushBullet API key.
     */
    public function __construct(private string $apiKey)
    {
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
        //TODO:: Didn't find the limit in PushBullet documentation
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

            return $response;
        } catch (Exception $e) {
            return false;
        }
    }
}
