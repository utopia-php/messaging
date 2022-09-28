<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;

class Twilio extends Base
{
    /**
     * @param string $accountSid Twilio Account SID
     * @param string $authToken Twilio Auth Token
     */
    public function __construct(
        private string $accountSid,
        private string $authToken
    ) {
    }

    public function getName(): string
    {
        return 'Twilio';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function sendMessage(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json",
            headers: [
                'Authorization: Basic ' . base64_encode("{$this->accountSid}:{$this->authToken}")
            ],
            body: \http_build_query([
                'Body' => $message->getContent(),
                'From' => '+' . $message->getFrom(),
                'To' => '+' . $message->getTo()[0]
            ]),
        );
    }
}
