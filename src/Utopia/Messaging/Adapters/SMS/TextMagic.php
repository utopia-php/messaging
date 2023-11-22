<?php

namespace Utopia\Messaging\Adapters\SMS;

// Reference Material
// https://www.textmagic.com/docs/api/send-sms/#How-to-send-bulk-text-messages

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class Textmagic extends SMSAdapter
{
    /**
     * @param  string  $username Textmagic account username
     * @param  string  $apiKey Textmagic account API key
     */
    public function __construct(
        private string $username,
        private string $apiKey,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return 'Textmagic';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
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

        $result = $this->request(
            method: 'POST',
            url: 'https://rest.textmagic.com/api/v2/messages',
            headers: [
                "X-TM-Username: {$this->username}",
                "X-TM-Key: {$this->apiKey}",
            ],
            body: \http_build_query([
                'text' => $message->getContent(),
                'from' => \ltrim($this->from ?? $message->getFrom(), '+'),
                'phones' => \implode(',', $to),
            ]),
        );

        return $result;
    }
}
