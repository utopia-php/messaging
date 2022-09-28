<?php

namespace Utopia\Messaging\Adapters\SMS;

// Reference Material
// https://www.textmagic.com/docs/api/send-sms/#How-to-send-bulk-text-messages

use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS as SMSAdapter;

class TextMagic extends SMSAdapter
{
    /**
     * @param string $username TextMagic account username
     * @param string $apiKey TextMagic account API key
     */
    public function __construct(
        private string $username,
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return 'TextMagic';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $to = \array_map(
            fn($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        return $this->request(
            method: 'POST',
            url: 'https://rest.textmagic.com/api/v2/messages',
            headers: [
                "X-TM-Username: {$this->username}",
                "X-TM-Key: {$this->apiKey}",
            ],
            body: [
                'text' => $message->getContent(),
                'from' => \ltrim($message->getFrom(), '+'),
                'phones' => \implode(',', $to),
            ],
        );
    }
}
