<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class Mailchimp extends EmailAdapter
{
    /**
     * @param  string  $apiKey Your Mailchimp API key to authenticate with the API.
     */
    public function __construct(
        private string $apiKey,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Mailchimp';
    }

    /**
     * Get adapter description.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000; // Depends based on the pricing plan
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
        // $body = 
        // );
        // var_dump($body);
        return $this->request(
            method: 'POST',
            url: 'https://mandrillapp.com/api/1.0/messages/send',
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode(array(
                'key' => $this->apiKey,
                'message' => [
                    'html' => $message->isHtml() ? $message->getContent() : null,
                    'text' => $message->isHtml() ? null : $message->getContent(),
                    'subject' => $message->getSubject(),
                    'from_email' => $message->getFrom(),
                    'to' => \array_map(
                        fn ($to) => ['email' => $to],
                        $message->getTo()
                    ),
                    'attachments' => $message->getAttachments() ? \array_map(
                        fn ($attachement) => [
                            "type" => $attachement['type'], 
                            "name" => $attachement['name'], 
                            "content" => $attachement['content']
                        ],
                        $message->getAttachments()
                        ) : null,
                    ],
                )
            )
        );
    }
}

?>