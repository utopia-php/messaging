<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

/**
 * Fast2SMS Adapter for sending SMS messages using Fast2SMS API
 *
 * This adapter supports both DLT (Distributed Ledger Technology) and Quick SMS routes
 * For API documentation, see: https://docs.fast2sms.com
 */
class Fast2SMS extends SMSAdapter
{
    protected const NAME = 'Fast2SMS';
    protected const API_ENDPOINT = 'https://www.fast2sms.com/dev/bulkV2';

    /**
     * Create a new Fast2SMS adapter instance
     *
     * @param string $apiKey Your Fast2SMS API authorization key
     * @param string $senderId Your 3-6 letter DLT approved Sender ID (e.g., "FSTSMS"). Required for DLT route
     * @param string $messageId Your DLT approved Message ID template. Required for DLT route
     * @param string[] $variableValues Array of values for variables in DLT template message. Optional for DLT route
     * @param bool $useDLT Whether to use DLT route (true) or Quick SMS route (false)
     *
     * @see https://docs.fast2sms.com/#dlt-sms
     * @see https://docs.fast2sms.com/#quick-sms
     */
    public function __construct(
        private string $apiKey,
        private string $senderId = '',
        private string $messageId = '',
        private array $variableValues = [],
        private bool $useDLT = false
    ) {
    }

    /**
     * Get the name of the SMS adapter
     *
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get maximum number of messages that can be sent in a single request
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * Process the SMS message and send it using Fast2SMS API
     *
     * @param SMSMessage $message The SMS message to be processed
     * @return array<string, mixed> The response from the API
     */
    protected function process(SMSMessage $message): array
    {
        $numbers = implode(',', $message->getTo());

        $payload = [
            'numbers' => $numbers,
            'flash' => 0,
        ];

        if ($this->useDLT) {
            $payload['route'] = 'dlt';
            $payload['sender_id'] = $this->senderId;
            $payload['message'] = $this->messageId;

            if (!empty($this->variableValues)) {
                $payload['variables_values'] = implode('|', $this->variableValues);
            }
        } else {
            $payload['route'] = 'q';
            $payload['message'] = $message->getContent();
        }

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: self::API_ENDPOINT,
            headers: [
                'authorization: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            body: $payload
        );

        $res = $result['response'];
        if ($result['statusCode'] === 200 && isset($res['return']) && $res['return'] === true) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            $errorMessage = $res['message'] ?? 'Unknown error' . ' Status Code: ' . ($res['status_code'] ?? 'Unknown');
            foreach ($message->getTo() as $to) {
                $response->addResult($to, $errorMessage);
            }
        }

        return $response->toArray();
    }
}
