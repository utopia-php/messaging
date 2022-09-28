<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;

class Msg91 extends Base
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
        return 'Msg91';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function sendMessage(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://api.msg91.com/api/v5/flow/',
            headers: [
                "content-type: application/json",
                "authkey: {$this->secret}",
            ],
            body: \json_encode([
                'sender' => $this->user,
                'otp' => $message->getContent(),
                'flow_id' => $message->getFrom(),
                'recipients' => [\array_map(
                    fn ($to) => ['mobiles' => $to],
                    $message->getTo()
                )]
            ]),
        );
    }
}