<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.smsapi.com/docs/#2-single-sms
class SMSApi extends SMSAdapter
{
    /**
     * @param  string  $apiToken SMSApi token
     */
    public function __construct(
        private string $apiToken
    ) {
    }

    public function getName(): string
    {
        return 'SMSApi';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
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
            url: 'https://api.smsapi.com/sms.do',
            headers: [
                'Authorization: Bearer '.$this->apiToken,
                'content-type: application/json',
            ],
            body: \json_encode([
                'from' => $message->getFrom(),          //sendername made in https://ssl.smsapi.com/sms_settings/sendernames
                'to' => $message->getTo()[0],           //destination number
                'message' => $message->getContent(),    //message content
            ]),
        );
    }
}
