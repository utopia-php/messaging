<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
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
     */
    protected function process(EmailMessage $message): string
    {
        $usDomain = 'api.mailgun.net';
        $euDomain = 'api.eu.mailgun.net';

        $domain = $this->isEU ? $euDomain : $usDomain;

        $body = [
            'to' => \implode(',', $message->getTo()),
            'from' => "{$message->getFromName()}<{$message->getFromEmail()}>",
            'subject' => $message->getSubject(),
            'text' => $message->isHtml() ? null : $message->getContent(),
            'html' => $message->isHtml() ? $message->getContent() : null,
        ];

        if (! \is_null($message->getCC())) {
            foreach ($message->getCC() as $cc) {
                if (!empty($cc['name'])) {
                    $body['cc'] = "{$body['cc']},{$cc['name']}<{$cc['email']}>";
                } else {
                    $body['cc'] = "{$body['cc']}, <{$cc['email']}>";
                }
            }
        }

        if (! \is_null($message->getBCC())) {
            foreach ($message->getBCC() as $bcc) {
                if (!empty($bcc['name'])) {
                    $body['bcc'] = "{$body['bcc']},{$bcc['name']}<{$bcc['email']}>";
                } else {
                    $body['bcc'] = "{$body['bcc']}, <{$bcc['email']}>";
                }
            }
        }

        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: "https://$domain/v3/{$this->domain}/messages",
            headers: [
                'Authorization: Basic '.base64_encode('api:'.$this->apiKey),
                'h:Reply-To: '."{$message->getReplyToName()}<{$message->getReplyToEmail()}>",
            ],
            body: \http_build_query($body),
        );

        $statusCode = $result['statusCode'];

        if ($statusCode >= 200 && $statusCode < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to, $result['response']['message']);
            }
        }

        return \json_encode($response->toArray());
    }
}
