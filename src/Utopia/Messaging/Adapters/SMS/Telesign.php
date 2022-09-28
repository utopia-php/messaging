<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS\SMS as SMSAdapter;

// Reference Material
// https://developer.telesign.com/enterprise/reference/sendbulksms

class Telesign extends SMSAdapter
{
    /**
     * @param string $username Telesign account username
     * @param string $password Telesign account password
     */
    public function __construct(
        private string $username,
        private string $password
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

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $to = \array_map(
            fn($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        return $this->request(
            method: 'POST',
            url: 'https://rest-ww.telesign.com/v1/verify/bulk_sms',
            headers: [
                'Authorization: Basic ' . base64_encode("{$this->username}:{$this->password}")
            ],
            body: [
                'template' => $message->getContent(),
                'recipients' => \implode(',', $to)
            ],
        );
    }
}
