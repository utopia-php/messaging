<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class CM extends SMSAdapter
{
    /**
     * @param  string  $apiKey CM API Key
     */
    public function __construct(
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return 'CM';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 50000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://api.cmtelecom.com/v1.0/message',
            headers: [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->apiKey,
            ],
            body: json_encode([
                'from' => $message->getFrom(),
                'to' => $message->getTo()[0],
                'body' => $message->getContent(),
            ]),
        );
    }
}
