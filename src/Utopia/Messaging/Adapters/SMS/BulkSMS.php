<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.bulksms.com/developer/json/v1/#tag/Message
class BulkSMS extends SMSAdapter
{
    /**
     * @param  string  $username BulkSMS Username
     * @param  string  $password BulkSMS Password
     */
    public function __construct(
        private string $username,
        private string $password
    ) {
    }

    public function getName(): string
    {
        return 'BulkSMS';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 500;
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
            url: "https://api.bulksms.com/v1/messages?auto-unicode=true&longMessageMaxParts=30",
            headers: [
                'content-type: application/json',
                'Authorization: Basic '.base64_encode("{$this->username}:{$this->password}")
            ],
            body: \json_encode([
                'from' => $message->getFrom(),
                'to' => $message->getTo(),
                'body' => $message->getContent()
            ]),
        );
    }
}