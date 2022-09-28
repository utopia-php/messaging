<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS as SMSAdapter;

class TwilioNotify extends SMSAdapter
{
    /**
     * @param string $accountSid Twilio Account SID
     * @param string $authToken Twilio Auth Token
     */
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private string $serviceSid,
    ) {
    }

    public function getName(): string
    {
        return 'Twilio';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 10000;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: "https://notify.twilio.com/v1/Services/{$this->serviceSid}/Notifications",
            headers: [
                'Authorization: Basic ' . base64_encode("{$this->accountSid}:{$this->authToken}")
            ],
            body: \http_build_query([
                'Body' => $message->getContent(),
                'ToBinding' => \json_encode(\array_map(
                    fn($to) => ['binding_type' => 'sms', 'address' => $to],
                    $message->getTo()
                )),
            ]),
        );
    }
}
