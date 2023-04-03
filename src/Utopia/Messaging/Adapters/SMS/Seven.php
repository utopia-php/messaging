<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.seven.io/en/docs/gateway/http-api/sms-dispatch/
class Seven extends SMSAdapter
{
    /**
     * @param  string  $apiKey Seven API token
     */
    public function __construct(
  private string $apiKey
 ) {
    }

    public function getName(): string
    {
        return 'Seven';
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
        return $this->request(
            method: 'POST',
            url: 'https://gateway.sms77.io/api/sms',
            headers: [
                'Authorization: Basic '.$this->apiKey,
                'content-type: application/json',
            ],
            body: \json_encode([
                'from' => $message->getFrom(),
                'to' => \implode(',', $message->getTo()),
                'text' => $message->getContent(),
            ]),
        );
    }
}
