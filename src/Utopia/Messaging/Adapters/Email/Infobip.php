<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Adapters\Email as EmailAdapter;

class Infobip extends EmailAdapter
{
    public function __construct(private string $apiKey) 
    {
    }

    public function getName(): string
    {
        return 'Infobip';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(Email $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://3ggzv1.api.infobip.com',
            headers: [
                'Content-Type: multipart/form-data',
                'Authorization: App ' . $this->apiKey,
            ],
            body: \json_encode([
                'subject' => $message->getSubject(),
                'to' => \array_map(
                            fn ($to) => ['email' => $to],
                            $message->getTo()
                        ),
                'from' => [
                    'email' => $message->getFrom(),
                ],
                'attachment' => $message->getAttachments(),
                'html' => $message->isHtml(),

            ]),
        );
    }
}