<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

// Reference Material
// https://www.infobip.com/docs/api/channels/sms/sms-messaging/outbound-sms/send-sms-message
class Infobip extends SMSAdapter
{
    protected const NAME = 'Infobip';

    /**
     * @param  string  $apiBaseUrl Infobip API Base Url
     * @param  string  $apiKey Infobip API Key
     */
    public function __construct(
        private string $apiBaseUrl,
        private string $apiKey,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
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
        $to = \array_map(fn ($number) => ['to' => \ltrim($number, '+')], $message->getTo());

        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: "https://{$this->apiBaseUrl}/sms/2/text/advanced",
            headers: [
                'Authorization: App '.$this->apiKey,
                'content-type: application/json',
            ],
            body: \json_encode([
                'messages' => [
                    'text' => $message->getContent(),
                    'from' => $this->from ?? $message->getFrom(),
                    'destinations' => $to,
                ],
            ]),
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResult($to, 'Unknown error.');
            }
        }

        return $response->toArray();
    }
}
