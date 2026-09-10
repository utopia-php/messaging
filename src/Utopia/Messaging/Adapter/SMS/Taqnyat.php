<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class Taqnyat extends SMSAdapter
{
    protected const NAME = 'Taqnyat';

    /**
     * @param  string  $apiKey Taqnyat API Key
     * @param  string  $senderId Taqnyat Sender ID
     */
    public function __construct(
        private string $apiKey,
        private string $senderId,
    ) {}

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
        $to = \array_map(
            fn($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: 'https://api.taqnyat.sa/v1/messages',
            headers: [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            body: [
                'sender' => $this->senderId,
                'recipients' => $to,
                'body' => $message->getContent(),
            ],
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                if (!\is_null($result['response']['message'] ?? null)) {
                    $response->addResult($to, $result['response']['message']);
                } else {
                    $response->addResult($to, 'Unknown error');
                }
            }
        }

        return $response->toArray();
    }
}
