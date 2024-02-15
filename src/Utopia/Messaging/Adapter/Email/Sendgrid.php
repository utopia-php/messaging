<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Sendgrid extends EmailAdapter
{
    protected const NAME = 'Sendgrid';

    /**
     * @param  string  $apiKey Your Sendgrid API key to authenticate with the API.
     * @return void
     */
    public function __construct(private string $apiKey)
    {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EmailMessage $message): array
    {
        $personalizations = [
            [
                'to' => \array_map(
                    fn ($to) => ['email' => $to],
                    $message->getTo()
                ),
                'subject' => $message->getSubject(),
            ],
        ];

        if (!\is_null($message->getCC())) {
            foreach ($message->getCC() as $cc) {
                if (!empty($cc['name'])) {
                    $personalizations[0]['cc'][] = [
                        'name' => $cc['name'],
                        'email' => $cc['email'],
                    ];
                } else {
                    $personalizations[0]['cc'][] = [
                        'email' => $cc['email'],
                    ];
                }
            }
        }

        if (!\is_null($message->getBCC())) {
            foreach ($message->getBCC() as $bcc) {
                if (!empty($bcc['name'])) {
                    $personalizations[0]['bcc'][] = [
                        'name' => $bcc['name'],
                        'email' => $bcc['email'],
                    ];
                } else {
                    $personalizations[0]['bcc'][] = [
                        'email' => $bcc['email'],
                    ];
                }
            }
        }

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://api.sendgrid.com/v3/mail/send',
            headers: [
                'Authorization: Bearer '.$this->apiKey,
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'personalizations' => $personalizations,
                'reply_to' => [
                    'name' => $message->getReplyToName(),
                    'email' => $message->getReplyToEmail(),
                ],
                'from' => [
                    'name' => $message->getFromName(),
                    'email' => $message->getFromEmail(),
                ],
                'content' => [
                    [
                        'type' => $message->isHtml() ? 'text/html' : 'text/plain',
                        'value' => $message->getContent(),
                    ],
                ],
            ]),
        );

        $statusCode = $result['statusCode'];

        if ($statusCode === 202) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                if (\is_string($result['response'])) {
                    $response->addResultForRecipient($to, $result['response']);
                } elseif (!\is_null($result['response']['errors'][0]['message'] ?? null)) {
                    $response->addResultForRecipient($to, $result['response']['errors'][0]['message']);
                } else {
                    $response->addResultForRecipient($to, 'Unknown error');
                }
            }
        }

        return $response->toArray();
    }
}
