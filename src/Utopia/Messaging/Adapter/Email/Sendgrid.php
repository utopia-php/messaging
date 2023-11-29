<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Sendgrid extends EmailAdapter
{
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
        return 'Sendgrid';
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
    protected function process(EmailMessage $message): string
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

        if (! \is_null($message->getCcName()) && ! \is_null($message->getCcEmail())) {
            $personalizations[0]['cc'] = [
                'name' => $message->getCcName(),
                'email' => $message->getCcEmail(),
            ];
        }

        if (! \is_null($message->getBccName()) && ! \is_null($message->getBccEmail())) {
            $personalizations[0]['bcc'] = [
                'name' => $message->getBccName(),
                'email' => $message->getBccEmail(),
            ];
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
                    'email' => $message->getReplyTo(),
                ],
                'from' => [
                    'name' => $message->getFrom(),
                    'email' => $message->getSenderEmailAddress(),
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
            foreach ($message->getTo() as $recipient) {
                $response->addResultForRecipient($recipient);
            }
        } else {
            foreach ($message->getTo() as $recipient) {
                $response->addResultForRecipient($recipient, $result['response']['errors'][0]['message']);
            }
        }

        return \json_encode($response->toArray());
    }
}
