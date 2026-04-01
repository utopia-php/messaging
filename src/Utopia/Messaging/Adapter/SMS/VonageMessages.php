<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Adapter\VonageMessagesBase;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

/**
 * Vonage Messages API SMS Adapter.
 *
 * Uses the newer Vonage Messages API (V1) instead of the older SMS API.
 * The Messages API is cheaper and supports multiple message types.
 *
 * Reference: https://developer.vonage.com/en/api/messages
 */
class VonageMessages extends SMSAdapter
{
    use VonageMessagesBase;

    protected const NAME = 'Vonage Messages';

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
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $to = \ltrim($message->getTo()[0], '+');
        $from = $this->from ?? $message->getFrom();

        $response = new Response($this->getType());

        if (empty($from)) {
            $response->addResult($message->getTo()[0], 'The "from" field is required for the Vonage Messages API.');
            return $response->toArray();
        }

        $from = \ltrim($from, '+');

        $result = $this->request(
            method: 'POST',
            url: $this->getApiEndpoint(),
            headers: $this->getRequestHeaders(),
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
            $errorMessage = 'Unknown error';
            if (isset($result['response']['detail'])) {
                $errorMessage = $result['response']['detail'];
            } elseif (isset($result['response']['title'])) {
                $errorMessage = $result['response']['title'];
            } elseif (!empty($result['error'])) {
                $errorMessage = $result['error'];
            }

            $response->addResult($message->getTo()[0], $errorMessage);
        }

        return $response->toArray();
    }
}
