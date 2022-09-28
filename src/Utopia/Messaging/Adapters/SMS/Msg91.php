<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://docs.msg91.com/p/tf9GTextN/e/7WESqQ4RLu/MSG91

class Msg91 extends \Utopia\Messaging\Adapters\SMS\SMS
{
    /**
     * @param string $senderId Msg91 Sender ID
     * @param string $authKey Msg91 Auth Key
     */
    public function __construct(
        private string $senderId,
        private string $authKey
    ) {
    }

    public function getName(): string
    {
        return 'Msg91';
    }

    public function getMaxMessagesPerRequest(): int
    {
        // TODO: Find real limit
        return 1000;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://api.msg91.com/api/v5/flow/',
            headers: [
                "content-type: application/json",
                "authkey: {$this->authKey}",
            ],
            body: \json_encode([
                'sender' => $this->senderId,
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
