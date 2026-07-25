<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Response;

class SparkPost extends EmailAdapter
{
    public function __construct(
        private string $apiKey,
        private bool $isEu = false
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'SparkPost';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(Email $message): array
    {
        $usDomain = 'api.sparkpost.com';
        $euDomain = 'api.eu.sparkpost.com';

        $domain = $this->isEu ? $euDomain : $usDomain;

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: "https://$domain/api/v1/transmissions",
            headers: [
                'Authorization: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            body: [
                'options' => [
                    'sandbox' => false,
                ],
                'recipients' => [
                    [
                        'address' => [
                            'email' => $message->getTo()[0],
                        ],
                    ],
                ],
                'content' => [
                    'from' => [
                        'email' => $message->getFrom(),
                    ],
                    'subject' => $message->getSubject(),
                    'html' => $message->isHtml() ? $message->getContent() : null,
                    'text' => $message->isHtml() ? null : $message->getContent(),
                ],
            ],
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->addResult($message->getTo()[0]);
        } else {
            $error = $result['response']['errors'][0]['message'] ?? $result['error'] ?? 'Unknown error';
            $response->addResult($message->getTo()[0], $error);
        }

        return $response->toArray();
    }
}
