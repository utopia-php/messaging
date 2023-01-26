<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class Telnyx extends SMSAdapter
{
    /**
     * @param  string  $accountSid Twilio Account SID
     * @param  string  $authToken Twilio Auth Token
     */
    public function __construct(
        private string $apiKey,
    ) {
    }

    public function getName(): string
    {
        return 'Telnyx';
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
        return $this->request(
            method: 'POST',
            url: "https://api.telnyx.com/v2/messages",
            headers: [
                'Authorization: Bearer '.base64_encode($this->apiKey),
                'Content-Type: application/json'
            ],
            body: \json_encode([
                'text' => $message->getContent(),
                'from' => $message->getFrom(),
                'to' => $message->getTo()[0],
            ]),
        );
    }
}
