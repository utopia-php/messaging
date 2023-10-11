<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Netcore extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Netcore API key to authenticate with the API. (Ref: https://cpaasdocs.netcorecloud.com/docs/pepipost-api/ZG9jOjQyMTU3NjQ0-authentication)
     * @param  bool  $isEU Whether to use the EU domain or not.
     */
    public function __construct(
        private string $apiKey,
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
        return 'Netcore';
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
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
        $usDomain = 'emailapi.netcorecloud.net';
        $euDomain = 'apieu.netcorecloud.net';

        $domain = $this->isEU ? $euDomain : $usDomain;

        $body = [
            'to' => \implode(',', $message->getTo()),
            'from' => $message->getFrom(),
            'subject' => $message->getSubject(),
            'content' => [
                'type' => $message->isHtml() ? 'html' : 'amp',
                'value' => $message->getContent(),
            ],
        ];

        $response = $this->request(
            method: 'POST',
            url: "https://$domain/v5.1/mail/send",
            headers: [
                'apiKey: '.base64_encode('api:'.$this->apiKey),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            body: \json_encode($body)
        );

        return $response;
    }
}
