<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class Telnyx extends SMSAdapter
{
    protected const NAME = 'Telnyx';

    /**
     * @param string $apiKey Telnyx API Key
     * @param string $from Telnyx phone number or profile ID
     */
    public function __construct(
        private string $apiKey,
        private string $from,
    ) {
        parent::__construct();
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        foreach ($message->getTo() as $to) {
            $result = $this->request(
                method: 'POST',
                url: 'https://api.telnyx.com/v2/messages',
                headers: [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$this->apiKey}",
                ],
                body: [
                    'from' => $this->from,
                    'to' => $to,
                    'text' => $message->getContent(),
                ]
            );

            if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
                $response->incrementDeliveredTo();
                $response->addResult($to);
            } else {
                $response->addResult($to, $result['response']['errors'][0]['detail'] ?? 'Unknown error');
            }
        }

        return $response->toArray();
    }
}
