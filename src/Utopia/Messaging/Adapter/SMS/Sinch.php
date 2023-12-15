<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

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
        private string $apiToken,
        private ?string $from = null
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
    protected function process(SMSMessage $message): array
    {
        $to = \array_map(fn ($number) => \ltrim($number, '+'), $message->getTo());

        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: "https://sms.api.sinch.com/xms/v1/{$this->servicePlanId}/batches",
            headers: [
                'Authorization: Bearer '.$this->apiToken,
                'content-type: application/json',
            ],
            body: \json_encode([
                'from' => $this->from ?? $message->getFrom(),
                'to' => $to,
                'body' => $message->getContent(),
            ]),
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to, 'Unknown error.');
            }
        }

        return $response->toArray();
    }
}
