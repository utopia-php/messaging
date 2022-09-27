<?php

namespace Utopia\Messaging\SMS;

class Mock extends SMSAdapter
{
    /**
     * @param string $user User ID
     * @param string $secret User secret
     */
    public function __construct(
        private string $user,
        private string $secret
    )
    {
    }

    public function getName(): string
    {
        return 'Mock';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    protected function sendMessage(SMSMessage $message): string
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
                'to' => \join(',', $message->getTo()),
            ]),
        );
    }
}