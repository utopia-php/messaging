<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Response;

class Mailgun extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Mailgun API key to authenticate with the API.
     * @param  string  $domain Your Mailgun domain to send messages from.
     */
    public function __construct(
        private string $apiKey,
        private string $domain,
        private bool $isEU = false
    ) {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return 'Mailgun';
    }

    /**
     * Get adapter description.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
        $usDomain = 'api.mailgun.net';
        $euDomain = 'api.eu.mailgun.net';

        $domain = $this->isEU ? $euDomain : $usDomain;

        $response = new Response($this->getType());

        $result = \json_decode($this->request(
            method: 'POST',
            url: "https://$domain/v3/{$this->domain}/messages",
            headers: [
                'Authorization: Basic '.base64_encode('api:'.$this->apiKey),
            ],
            body: \http_build_query([
                'to' => \implode(',', $message->getTo()),
                'from' => $message->getFrom(),
                'subject' => $message->getSubject(),
                'text' => $message->isHtml() ? null : $message->getContent(),
                'html' => $message->isHtml() ? $message->getContent() : null,
            ]),
        ), true);

        $statusCode = $result['statusCode'];

        if ($statusCode >= 200 && $statusCode < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addToDetails($to);
            }
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            foreach ($message->getTo() as $to) {
                $response->addToDetails($to, $result['response']['message']);
            }
        }

        return \json_encode($response->toArray());
    }
}
