<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://developers.sinch.com/docs/sms/api-reference/
class Sinch extends SMSAdapter
{
    /**
     * @param  string  $servicePlanId Sinch Service plan ID
     * @param  string  $apiToken Sinch API token
     */
    public function __construct(
        private string $servicePlanId,
        private string $apiToken
    ) {
    }

    public function getName(): string
    {
        return 'Sinch';
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
        $to = \array_map(fn ($number) => \ltrim($number, '+'), $message->getTo());

        return $this->request(
            method: 'POST',
            url: "https://sms.api.sinch.com/xms/v1/{$this->servicePlanId}/batches",
            headers: [
                'Authorization: Bearer '.$this->apiToken,
                'content-type: application/json',
            ],
            body: \json_encode([
                'from' => $message->getFrom(),
                'to' => $to,
                'body' => $message->getContent(),
            ]),
        );
    }
}
