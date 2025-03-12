<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Adapters\Email as EmailAdapter;

class Brevo extends EmailAdapter
{
    public function __construct(private string $apiKey) 
    {
    }

    public function getName(): string
    {
        return 'Brevo';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(Email $message): string
    {
        $bodyKey = $message->isHtml() ? 'htmlContent' : 'textContent';
        return $this->request(
            method: 'POST',
            url: 'https://api.brevo.com/v3/smtp/email',
            headers: [
                'accept: application/json',
                'Content-Type: application/json',
                'api-key: '.$this->apiKey,
            ],
            body: \json_encode([
                "sender" => [
                    "email" => $message->getFrom()
                ],
                "to" => \array_map(
                    fn ($to) => ['email' => $to],
                    $message->getTo()
                ),
                "subject" => $message->getSubject(),
                $bodyKey => $message->getContent(),
            ])
        );
    }
}
