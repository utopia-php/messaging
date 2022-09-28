<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;

class Telesign extends Base
{
    /**
     * @param string $user Telesign account username
     * @param string $secret Telesign account password
     */
    public function __construct(
        private string $user,
        private string $secret
    ) {
    }

    public function getName(): string
    {
        return 'Telesign';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function sendMessage(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://rest-ww.telesign.com/v1/verify/bulk_sms',
            headers: [
                'Authorization: Basic ' . base64_encode("{$this->user}:{$this->secret}")
            ],
            body: [
                'template' => $message->getContent(),
                'recipients' => \join(',', $message->getTo())
            ],
        );
    }
}