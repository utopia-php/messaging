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
        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://api.sendgrid.com/v3/mail/send',
            headers: [
                'Authorization: Bearer '.$this->apiKey,
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'personalizations' => [
                    [
                        'to' => \array_map(
                            fn ($to) => ['email' => $to],
                            $message->getTo()
                        ),
                        'subject' => $message->getSubject(),
                    ],
                ],
                'from' => [
                    'email' => $message->getFrom(),
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
                $response->addToDetails($recipient);
            }
        } else {
            foreach ($message->getTo() as $recipient) {
                $response->addToDetails($recipient, $result['response']['errors'][0]['message']);
            }
        }

        return \json_encode($response->toArray());
    }
}
