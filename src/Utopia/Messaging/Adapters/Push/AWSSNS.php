<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class AWSSNS extends PushAdapter
{
    /**
     * @param string $apiGatewayUrl
     */
    public function __construct(
        private string $apiGatewayUrl
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'AWSSNS';
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
     * @throws \Exception
     */
    protected function process(Push $message): string
    {
        // Create the payload
        $payload = [
            'message' => $message->getData()
        ];

        // Make the HTTP request
        $response = $this->request(
            method: 'POST',
            url: $this->apiGatewayUrl,
            headers: [
                'Content-Type: application/json',
            ],
            body: json_encode($payload)
        );

        return $response;
    }
}
