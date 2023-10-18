<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Mandrill extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Mandrill API key to authenticate with the API.
     * @return void
     */
    public function __construct(private string $apiKey)
    {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Mandrill';
    }

    /**
     * Get max messages per request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     *
     * @param  Email  $message
     * @return string
     *
     * @throws Exception
     */
    protected function process(Email $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://mandrillapp.com/api/1.0/messages/send',
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode([
                "key" => $this->apiKey,
                "message" => [
                    "subject" => $message->getSubject(),
                    "html" => $message->isHTML() ? $message->getContent() : null,
                    "text" => $message->isHTML() ? null : $message->getContent(),
                    "from_email" => $message->getFrom(),
                    "to" => \array_map(
                            fn ($to) => ['email' => $to],
                            $message->getTo()
                        ),
                ]
            ]),
        );
    }
}
