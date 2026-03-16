<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Helpers\JWT;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

trait VonageTrait
{
    /**
     * @param string $applicationId Vonage Application ID
     * @param string $privateKey Vonage Private Key
     * @param string|null $from Sender phone number or name
     */
    public function __construct(
        private string $applicationId,
        private string $privateKey,
        private ?string $from = null
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function processMessage(SMSMessage $message, string $channel): array
    {
        $payload = [
            'from' => $this->from ?? $message->getFrom(),
            'to' => \ltrim($message->getTo()[0], '+'),
            'message_type' => 'text',
            'text' => $message->getContent(),
            'channel' => $channel,
        ];

        $jwt = JWT::encode(
            [
                'application_id' => $this->applicationId,
                'iat' => \time(),
                'jti' => \bin2hex(\random_bytes(16)),
                'exp' => \time() + 3600,
            ],
            $this->privateKey,
            'RS256'
        );

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://api.nexmo.com/v1/messages',
            headers: [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $jwt,
                'Accept: application/json',
            ],
            body: $payload,
        );

        $statusCode = $result['statusCode'];
        $res = $result['response'];

        if ($statusCode >= 200 && $statusCode < 300 && isset($res['message_uuid'])) {
            $response->setDeliveredTo(1);
            $response->addResult($message->getTo()[0]);
        } else {
            $error = $res['detail'] ?? $res['title'] ?? 'Unknown error';
            $response->addResult($message->getTo()[0], $error);
        }

        return $response->toArray();
    }
}
