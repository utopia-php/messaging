<?php
namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class Gupshup extends SMSAdapter
{
    /**
     * @param  string  $apiKey Gupshup API Key
     */
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getName(): string
    {
        return 'Gupshup';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $url = 'https://api.gupshup.io/sm/api/v1/msg';
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        
        $data = [
            'channel' => 'sms',
            'src' => $message->getFrom(),
            'dst' => $message->getTo()[0],
            'text' => $message->getContent(),
        ];

        return $this->request(
            method: 'POST',
            url: $url,
            headers: $headers,
            body: json_encode($data),
        );
    }
}
