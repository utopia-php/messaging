<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class SMSGateApp extends SMSAdapter
{
    protected const NAME = 'SMS Gateway for Android™';
    protected const DEFAULT_API_ENDPOINT = 'https://api.sms-gate.app/3rdparty/v1';

    public function __construct(
        private readonly string $apiUsername,
        private readonly string $apiPassword,
        private readonly string $apiEndpoint = self::DEFAULT_API_ENDPOINT,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 10;
    }

    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        $body = [
            'textMessage' => [
                'text' => $message->getContent(),
            ],
            'phoneNumbers' => $message->getTo(),
        ];

        $result = $this->request(
            method: 'POST',
            url: $this->apiEndpoint . '/messages?skipPhoneValidation=true',
            headers: [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("{$this->apiUsername}:{$this->apiPassword}"),
            ],
            body: $body,
        );

        if ($result['statusCode'] === 202) {
            $success = 0;
            foreach ($result['response']['recipients'] as $recipient) {
                $response->addResult($recipient['phoneNumber'], $recipient['error'] ?? '');

                if ($recipient['state'] !== 'Failed') {
                    $success++;
                }
            }

            $response->setDeliveredTo($success);
        } else {
            $errorMessage = $result['response']['message'] ?? 'Unknown error';
            foreach ($message->getTo() as $recipient) {
                $response->addResult($recipient, $errorMessage);
            }
        }

        return $response->toArray();
    }
}
