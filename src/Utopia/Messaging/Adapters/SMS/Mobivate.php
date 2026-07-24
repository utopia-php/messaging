<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://wiki.mobivatebulksms.com/use-cases/send-batch-sms-messages
class Mobivate extends SMSAdapter
{
    /**
     * @param  string  $apiKey Mobivate API Key
     * @param  string  $routeId Mobivate routeId
     */
    public function __construct(
        private string $apiKey,
        private string $routeId
    ) {
    }

    public function getName(): string
    {
        return 'Mobivate';
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
    protected function process(SMS $message): string
    {
        $messages = [];
        foreach ($message->getTo() as $to) {
            $temp = [
                'originator' => $message->getFrom(),
                'recipient' => \ltrim($to, '+'),
                'text' => $message->getContent(),
            ];
            array_push($messages, $temp);
        }

        return $this->request(
            method: 'POST',
            url: 'https://api.mobivatebulksms.com/send/batch',
            headers: [
                'content-type: application/json',
                'Authorization: Bearer '.$this->apiKey,
            ],
            body: \json_encode([
                'routeId' => $this->routeId,
                'messages' => $messages,
            ]),
        );
    }
}
