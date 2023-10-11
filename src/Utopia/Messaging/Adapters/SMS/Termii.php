<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://developers.termii.com/messaging-api
class Termii extends SMSAdapter
{
    const SMSType = 'plain';

    const SMSChannel = 'generic';

    public function __construct(
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return 'Termii';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://api.ng.termii.com/api/sms/send',
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode(
                $this->getRequestBody(
                    to: $message->getTo(),
                    text: $message->getContent(),
                    from: $message->getFrom()
                )
            ),
        );
    }

    /**
     * Get the request body
     *
     * @param  array  $to  Phone number
     * @param  string  $text  Message to send
     * @param  string|null  $from Origin of the message
     */
    private function getRequestBody(array $to, string $text, string $from = null): array
    {
        if (empty($from)) {
            $from = '';
        }

        // removing + from numbers if exists
        $to = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $to
        );

        if (count($to) == 1) {
            $to = $to[0];
        }

        $body = [
            'api_key' => $this->apiKey,
            'to' => $to,
            'from' => $from,
            'sms' => $text,
            'type' => self::SMSType,
            'channel' => self::SMSChannel,
        ];

        return $body;
    }
}
