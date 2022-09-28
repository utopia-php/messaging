<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;

class Twilio extends Base
{
    /**
     * @param string $user Twilio Account SID
     * @param string $secret Twilio Auth Token
     */
    public function __construct(
        private string $user,
        private string $secret
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

    protected function sendMessage(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: "https://api.twilio.com/2010-04-01/Accounts/{$this->user}/Messages.json",
            headers: [
                'Authorization: Basic ' . base64_encode("{$this->user}:{$this->secret}")
            ],
            body: \http_build_query([
                'Body' => $message->getContent(),
                'From' => $message->getFrom(),
                'To' => \join(',', $message->getTo())
            ]),
        );
    }
}
