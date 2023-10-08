<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class ElasticEmail extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your ElasticEmail API key to authenticate with the API.
     * @param  string  $domain Your ElasticEmail domain to send messages from.
     */
    public function __construct(
        private string $apiKey,
        private string $domain,
        private bool $isEU = false
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'ElasticEmail';
    }

    /**
     * Get adapter description.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @param Email $message
     * @return string
     *
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
        $domain = 'api.ElasticEmail.com';

        $response = $this->request(
            method: 'POST',
            url: "https://$domain/v4/{$this->domain}/send",
            headers: [
                'Authorization: Basic '.base64_encode('api:'.$this->apiKey),
            ],
            body: http_build_query([
                'apiKey' => $this->apiKey,
                'from' => $message->getFrom(),
                'fromName' => $message->getFromName(),
                'subject' => $message->getSubject(),
                'bodyHtml' => $message->isHtml() ? $message->getContent() : null,
                'bodyText' => $message->isHtml() ? null : $message->getContent(),
                'to' => implode(',', $message->getTo()),            ]),
        );

        return $response;
    }
}
