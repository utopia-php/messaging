<?php

namespace Utopia\Messaging\Adapter\SMS;

// Reference Material
// https://www.textmagic.com/docs/api/send-sms/#How-to-send-bulk-text-messages

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Response;

class Vonage extends SMSAdapter
{
    /**
     * @param  string  $apiKey Vonage API Key
     * @param  string  $apiSecret Vonage API Secret
     */
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return 'Vonage';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMS $message): array
    {
        $to = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://rest.nexmo.com/sms/json',
            body: \http_build_query([
                'text' => $message->getContent(),
                'from' => $this->from ?? $message->getFrom(),
                'to' => $to[0], //\implode(',', $to),
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ]),
        );

        switch ($result['response']['messages'][0]['status']) {
            case 0:
                $response->setDeliveredTo(1);
                $response->addResultForRecipient($result['response']['messages'][0]['to']);
                break;
            default:
                $response->addResultForRecipient($message->getTo()[0], $result['response']['messages'][0]['error-text']);
        }

        return $response->toArray();
    }
}
