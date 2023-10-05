<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Postmark extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Postmark API key to authenticate with the API.
     */
    public function __construct(
        private string $apiKey
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Postmark';
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
        $bodyKey = $message->isHtml() ? 'HtmlBody' : 'TextBody';

        $response = $this->request(
            method: 'POST',
            url: 'https://api.postmarkapp.com/email',
            headers: [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Postmark-Server-Token: '.$this->apiKey,
            ],
            body: \json_encode([
                'To' => \implode(',', $message->getTo()),
                'From' => $message->getFrom(),
                'Subject' => $message->getSubject(),
                $bodyKey => $message->getContent(),
            ]),
        );

        return $response;
    }
}
