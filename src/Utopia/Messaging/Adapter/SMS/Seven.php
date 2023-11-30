<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;

// Reference Material
// https://www.seven.io/en/docs/gateway/http-api/sms-dispatch/
class Seven extends SMSAdapter
{
    /**
     * @param  string  $apiKey Seven API token
     */
    public function __construct(
        private string $apiKey,
        private ?string $from = null
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
    protected function process(SMSMessage $message): string
    {
        $result = $this->request(
            method: 'POST',
            url: 'https://gateway.sms77.io/api/sms',
            headers: [
                'Authorization: Basic '.$this->apiKey,
                'content-type: application/json',
            ],
            body: \json_encode([
                'from' => $this->from ?? $message->getFrom(),
                'to' => \implode(',', $message->getTo()),
                'text' => $message->getContent(),
            ]),
        );

        return \json_encode($result['response']);
    }
}
