<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://46elks.com/docs/send-sms
class fortysixelks extends SMSAdapter
{ 

     /**
     * @param  string  $apiKey 46elks API token
     */
    public function __construct(
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return '46elks';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this -> request (
            method:'POST',
            url:'https://api.46elks.com/a1/sms',
            headers: [
                'Authorization: Basic '.$this->apiKey,
                'Content-type: application/json',
            ],
            body: \json_encode([
                'message' => $message->getContent(),
                'from' => $message->getFrom(),
                'to' => $message->getTo(),
            ]),
        );
    }
}