<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class VonageMessages extends SMSAdapter
{
    protected const NAME = 'Vonage Messages';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly ?string $from = null,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    protected function process(SMSMessage $message): array
    {
        $to = \ltrim($message->getTo()[0], '+');
        $from = $this->from ?? $message->getFrom();
        $from = $from !== null ? \ltrim($from, '+') : null;

        $response = new Response($this->getType());

        if (empty($from)) {
            $response->addResult($message->getTo()[0], 'The "from" field is required for the Vonage Messages API.');
            return $response->toArray();
        }

        $result = $this->request(
            method: 'POST',
            url: 'https://api.vonage.com/v1/messages',
            headers: [
                'Authorization: Basic ' . \base64_encode("{$this->apiKey}:{$this->apiSecret}"),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            body: [
                'message_type' => 'text',
                'to' => $to,
                'from' => $from,
                'text' => $message->getContent(),
                'channel' => 'sms',
            ],
        );

        if ($result['statusCode'] === 202) {
            $response->setDeliveredTo(1);
            $response->addResult($message->getTo()[0]);
        } else {
            $errorMessage = "Error {$result['statusCode']}";

            if (\is_array($result['response'])) {
                if (isset($result['response']['detail'])) {
                    $errorMessage = $result['response']['detail'];
                } elseif (isset($result['response']['title'])) {
                    $errorMessage = $result['response']['title'];
                }
            } elseif (!empty($result['error'])) {
                $errorMessage = $result['error'];
            } elseif (\is_string($result['response']) && !empty($result['response'])) {
                $errorMessage = "Error {$result['statusCode']}: " . \mb_strimwidth(\strip_tags($result['response']), 0, 100, '...');
            }

            $response->addResult($message->getTo()[0], $errorMessage);
        }

        return $response->toArray();
    }
}
