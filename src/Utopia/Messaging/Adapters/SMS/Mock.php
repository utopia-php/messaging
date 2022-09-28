<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS\SMS as SMSAdapter;

class Mock extends SMSAdapter
{
    /**
     * @param string $user User ID
     * @param string $secret User secret
     */
    public function __construct(
        private string $user,
        private string $secret
    ) {
    }

    public function getName(): string
    {
        return 'Mock';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'http://request-catcher:5000/mock-sms',
            headers: [
                "content-type: application/json",
                "x-username: {$this->user}",
                "x-key: {$this->secret}",
            ],
            body: \json_encode([
                'message' => $message->getContent(),
                'from' => $message->getFrom(),
                'to' => \implode(',', $message->getTo()),
            ]),
        );
    }
}
