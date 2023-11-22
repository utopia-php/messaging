<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class Pushy extends PushAdapter
{
    /**
     * @param  string  $secretKey The secret API  key that pushy provides.
     */
    public function __construct(
        private string $secretKey,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Pushy';
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
     * @throws \Exception
     */
    public function process(Push $message): string
    {

        return $this->request(
            method: 'POST',
            url: "https://api.pushy.me/push?api_key={$this->secretKey}",
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'to' => $message->getTo(),
                'data' => $message->getData(),
                'notifications' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                    'badge' => $message->getBadge(),
                    'sound' => $message->getSound()
                ],
            ])
        );
    }

}