<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Mailtrap extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Mailtrap API key to authenticate with the API.
     * @return void
     */
    public function __construct(private string $apiKey)
    {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Mailtrap';
    }

    /**
     * Get max messages per request.
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
     * @param  Email  $message
     * @return string
     *
     * @throws Exception
     */
    protected function process(Email $message): string
    {
        $bodyKey = $message->isHtml() ? 'html' : 'text';

        $response= $this->request(
            method: 'POST',
            url: 'https://send.api.mailtrap.io/api/send',
            headers: [
                'Accept: application/json',
                'Api-Token: '.$this->apiKey,
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'to' => \array_map(
                    fn ($to) => ['email' => $to],
                    $message->getTo()
                ),
                "subject" =>$message->getSubject(),
                $bodyKey => $message->getContent(),
                'from' => [
                    'email' => $message->getFrom(),
                ],
            ]),
        );
        return $response;
    }
}

