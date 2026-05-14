<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class AfricasTalking extends SMSAdapter
{
    protected const NAME = 'AfricasTalking';

    private const API_URL = 'https://api.africasTalking.com/sms/1/action';

    public function __construct(
        private string $username,
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
        return 100;
    }

    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        $recipients = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        $from = $this->from ?? $message->getFrom();

        foreach ($recipients as $recipient) {
            $result = $this->request(
                method: 'POST',
                url: self::API_URL,
                headers: [
                    'Content-Type: application/x-www-form-urlencoded',
                    'apiKey: ' . $this->apiKey,
                ],
                body: [
                    'username' => $this->username,
                    'to' => $recipient,
                    'message' => $message->getContent(),
                    'from' => $from,
                ]
            );

            if ($result['statusCode'] === 201) {
                $response->incrementDeliveredTo();
                $response->addResult($recipient);
            } else {
                $errorMessage = 'Unknown error';
                if (isset($result['response']['errorMessage'])) {
                    $errorMessage = $result['response']['errorMessage'];
                } elseif (isset($result['response']['message'])) {
                    $errorMessage = $result['response']['message'];
                }
                $response->addResult($recipient, $errorMessage);
            }
        }

        return $response->toArray();
    }
}