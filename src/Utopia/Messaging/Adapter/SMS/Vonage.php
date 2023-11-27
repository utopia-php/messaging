<?php

namespace Utopia\Messaging\Adapter\SMS;

// Reference Material
// https://www.textmagic.com/docs/api/send-sms/#How-to-send-bulk-text-messages

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

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
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $to = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        return $this->request(
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
    }
}
