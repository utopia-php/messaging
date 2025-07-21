<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

/**
 * SMSGateApp adapter class.
 */
class SMSGateApp extends SMSAdapter {
    protected const NAME = 'SMS Gateway for Android™';
    protected const DEFAULT_API_ENDPOINT = 'https://api.sms-gate.app/3rdparty/v1';

    /**
     * @param string $apiUsername SMSGate username
     * @param string $apiPassword SMSGate password
     * @param string|null $apiEndpoint SMSGate API endpoint
     */
    public function __construct(
        private string $apiUsername,
        private string $apiPassword,
        private ?string $apiEndpoint = null,
    ) {
        $this->apiEndpoint = $this->apiEndpoint ?: self::DEFAULT_API_ENDPOINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxMessagesPerRequest(): int {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array {
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