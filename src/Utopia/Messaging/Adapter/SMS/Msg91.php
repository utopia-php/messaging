<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

// Reference Material
// https://docs.msg91.com/p/tf9GTextN/e/7WESqQ4RLu/MSG91

class Msg91 extends SMSAdapter
{
    protected const NAME = 'Msg91';

    /**
     * @param  string  $senderId Msg91 Sender ID
     * @param  string  $authKey Msg91 Auth Key
     * @param  string  $templateId Msg91 Template ID
     */
    public function __construct(
        private string $senderId,
        private string $authKey,
        private string $templateId,
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        // TODO: Find real limit
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $recipients = [];
        foreach ($message->getTo() as $recipient) {
            $recipients[] = [
                'mobiles' => \ltrim($recipient, '+'),
                'content' => $message->getContent(),
                'otp' => $message->getContent(),
            ];
        }

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://api.msg91.com/api/v5/flow/',
            headers: [
                'Content-Type: application/json',
                'Authkey: '. $this->authKey,
            ],
            body: [
                'sender' => $this->senderId,
                'template_id' => $this->templateId,
                'recipients' => $recipients,
            ],
        );

        if ($result['statusCode'] === 200) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResult($to, 'Unknown error');
            }
        }

        return $response->toArray();
    }
}
