<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://docs.msg91.com/p/tf9GTextN/e/7WESqQ4RLu/MSG91

class Msg91 extends SMSAdapter
{
    private string $templateId;

    /**
     * @param  string  $senderId Msg91 Sender ID
     * @param  string  $authKey Msg91 Auth Key
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

    public function setTemplate(string $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $recipients = [];
        foreach ($message->getTo() as $recipient) {
            $recipients[] = [
                'mobiles' => \ltrim($recipient, '+'),
                'content' => $message->getContent(),
                'otp' => $message->getContent(),
            ];
        }

        return $this->request(
            method: 'POST',
            url: 'https://api.msg91.com/api/v5/flow/',
            headers: [
                'content-type: application/json',
                "authkey: {$this->authKey}",
            ],
            body: \json_encode([
                'sender' => $this->senderId,
                'template_id' => $this->templateId,
                'recipients' => $recipients,
            ]),
        );
    }
}
