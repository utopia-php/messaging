<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

// Reference Material
// https://docs.clickatell.com/channels/sms-channels/sms-api-reference/#tag/SMS-API/operation/sendMessageREST_1
class Clickatell extends SMSAdapter
{
    protected const NAME = 'Clickatell';

    /**
     * @param  string  $apiKey Clickatell API Key
     */
    public function __construct(
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
        return 500;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: 'https://platform.clickatell.com/messages',
            headers: [
                'content-type: application/json',
                'Authorization: '.$this->apiKey,
            ],
            body: \json_encode([
                'content' => $message->getContent(),
                'from' => $this->from ?? $message->getFrom(),
                'to' => $message->getTo(),
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
