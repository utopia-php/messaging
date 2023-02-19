<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Sendgrid extends EmailAdapter
{
    public function __construct(
        private string $apiKey,
    ) {
    }

    public function getName(): string
    {
        return 'Sendgrid';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(Email $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://api.sendgrid.com/v3/mail/send',
            headers: [
                'Authorization: Bearer '.$this->apiKey,
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'personalizations' => [
                    [
                        'to' => \array_map(
                            fn ($to) => ['email' => $to],
                            $message->getTo()
                        ),
                        'subject' => $message->getSubject(),
                    ],
                ],
                'from' => [
                    'email' => $message->getFrom(),
                ],
                'content' => [
                    [
                        'type' => $message->isHtml() ? 'text/html' : 'text/plain',
                        'value' => $message->getContent(),
                    ],
                ],
            ])?: null,
        );
    }
}
