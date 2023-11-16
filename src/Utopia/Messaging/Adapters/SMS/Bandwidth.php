<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS as SMSAdapter;

class Bandwidth extends SMSAdapter
{
    private $apiKey;
    private $apiSecret;
    private $apiUrl;

    public function __construct(private string $apiKey, private string $apiSecret, private string $apiUrl)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->apiUrl = $apiUrl;
    }

    public function getName(): string
    {
        return 'Bandwidth';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: $this->apiUrl,
            headers: [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            ],
            body: json_encode([
                'to' => $message->getTo(),
                'from' => $message->getFrom(),
                'text' => $message->getBody(),
            ])
        );
    }
}
