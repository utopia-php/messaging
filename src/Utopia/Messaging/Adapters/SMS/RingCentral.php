<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://developers.ringcentral.com/sms-api

class RingCentral extends SMSAdapter
{
        /**
     * @param  string  $apiKey RingCentral API Key
     */
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getName(): string
    {
    return 'RingCentral';
    }

    public function getMaxMessagesPerRequest(): int
    {
    return 40;
    }

    protected function process(SMS $message): string
    {
        $url = 'https://platform.devtest.ringcentral.com/restapi/v1.0/account/accountId/extension/extensionId/sms';

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