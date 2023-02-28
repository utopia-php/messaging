<?php

namespace Utopia\Messaging\Adapters\SMS;

// Reference Material
// https://developers.clicksend.com/docs/rest/v3/#send-sms

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class ClickSend extends SMSAdapter
{
    /**
     * @param  string  $username ClickSend Username
     * @param  string  $apiKey ClickSend API Key
     */
    public function __construct(
        private string $username,
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return 'ClickSend';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $to = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        return $this->request(
            method: 'POST',
            url: 'https://rest.clicksend.com/v3/sms/send',
            headers: [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("{$this->username}:{$this->apiKey}"),
            ],
            body: \json_encode([
                'messages' => [
                    [
                        'body' => $message->getContent(),
                        'from' => $message->getFrom(),
                        'to' => \implode(',', $to),
                        'source' => 'appwrite'
                    ]
                ]
            ]),
        );
    }
}
