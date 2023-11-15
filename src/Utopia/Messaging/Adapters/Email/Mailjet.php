<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Mailjet extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Mailgun API key to authenticate with the API.
     * @param  string  $apiSecret Your Mailgun domain to send messages from.
     */
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Mailjet';
    }

    /**
     * Get adapter description.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
        $toEmail = [];
        foreach ($message->getTo() as $emails) {
            foreach ($emails as $email) {
                $toEmail[] = [
                    "Email" => $email,
                    "Name" => $email
                ];
            }
        }
        $apiKey = $this->apiKey;
        $apiSecret = $this->apiSecret;

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $message->getFrom(),
                        'Name' => $message->getFrom()
                    ],
                    'To' => $toEmail,
                    'Subject' => $message->getSubject(),
                    'TextPart' => $message->isHtml() ? null : $message->getContent(),
                    'HTMLPart' =>  $message->isHtml() ? $message->getContent() : null
                ]
            ]
        ];

        return $this->request(
            method: 'POST',
            url: 'https://api.mailjet.com/v3.1/send',
            headers: [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret")
            ],
            body: \json_encode($body)
        );
    }
}
