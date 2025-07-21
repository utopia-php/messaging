<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

//Reference: https://developers.africastalking.com/docs/authentication
//https://developers.africastalking.com/docs/request_headers
//https://developers.africastalking.com/docs/sms/sending/bulk

class AfricasTalking extends SMSAdapter
{
    /**
     * @param  string  $username AfricasTalking app username
     * @param  string  $apiKey AfricasTalking API Key
     */
    public function __construct(
        private string $username,
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return 'AfricasTalking';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {   
        return $this->request(
            method: 'POST',
            url: "https://api.sandbox.africastalking.com/version1/messaging",
            // live endpoint: url: "https://api.africastalking.com/version1/messaging ",
            headers: [
                'apiKey: '.$this->apiKey,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            body: \http_build_query([
                'username' => $this->username,
                'to' => $message->getTo()[0],
                'message' => $message->getContent(),
            ]),
        );
    }
}