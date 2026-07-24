<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class AfricasTalking extends SMSAdapter
{
    protected const NAME = 'AfricasTalking';

    private const API_URL = 'https://api.africasTalking.com/version1/messaging';

    public function __construct(
        private string $username,
        private string $apiKey,
        private ?string $from = null
    ) {
        parent::__construct();
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

        $recipients = $message->getTo();

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
                    $errorMessage = \is_string($result['response']['errorMessage'])
                        ? $result['response']['errorMessage']
                        : \json_encode($result['response']['errorMessage']);
                } elseif (isset($result['response']['message'])) {
                    $errorMessage = \is_string($result['response']['message'])
                        ? $result['response']['message']
                        : \json_encode($result['response']['message']);
                }
                $response->addResult($recipient, $errorMessage);
            }
        }

        return $response->toArray();
    }
}