<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Response;

class Netcore extends EmailAdapter
{
    public function __construct(
        private string $apiKey,
        private bool $isEU = false
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'Netcore';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(Email $message): array
    {
        $usDomain = 'emailapi.netcorecloud.net';
        $euDomain = 'apieu.netcorecloud.net';

        $domain = $this->isEU ? $euDomain : $usDomain;

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: "https://$domain/v5.1/mail/send",
            headers: [
                'apiKey: ' . \base64_encode('api:' . $this->apiKey),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            body: [
                'to' => \implode(',', $message->getTo()),
                'from' => $message->getFrom(),
                'subject' => $message->getSubject(),
                'content' => [
                    'type' => $message->isHtml() ? 'html' : 'amp',
                    'value' => $message->getContent(),
                ],
            ],
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->addResult(\implode(',', $message->getTo()));
        } else {
            $error = $result['response']['error'] ?? $result['error'] ?? 'Unknown error';
            $response->addResult(\implode(',', $message->getTo()), $error);
        }

        return $response->toArray();
    }
}
