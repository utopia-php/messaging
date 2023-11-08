<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Adapters\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Messages\SMS;

class GEOSMS extends SMSAdapter
{
    protected $defaultAdapter;

    protected $localAdapters = [];

    public function __construct(SMSAdapter $defaultAdapter)
    {
        $this->defaultAdapter = $defaultAdapter;
    }

    public function getName(): string
    {
        return 'GEOSMS';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
    }

    public function setLocal(string $callingCode, SMSAdapter $adapter): self
    {
        $this->localAdapters[$callingCode] = $adapter;

        return $this;
    }

    protected function process(SMS $message): string
    {
        $recipientsByCallingCode = $this->groupRecipientsByCallingCode($message->getTo());
        $responses = [];
        $errors = [];

        foreach ($recipientsByCallingCode as $callingCode => $recipients) {
            $adapter = isset($this->localAdapters[$callingCode])
                ? $this->localAdapters[$callingCode]
                : $this->defaultAdapter;

            try {
                $responses[] = $adapter->send(new SMS(
                    to: $recipients,
                    content: $message->getContent(),
                    from: $message->getFrom(),
                    attachments: $message->getAttachments()
                ));
            } catch (\Exception $e) {
                $errors[] = $e;
            }
        }

        if (count($errors) > 0) {
            throw new \Exception('Failed to send SMS to some recipients', 0, $errors[0]);
        }

        return $responses[0];
    }

    protected function groupRecipientsByCallingCode(array $recipients): array
    {
        $result = [];

        foreach ($recipients as $recipient) {
            $callingCode = CallingCode::fromPhoneNumber($recipient);
            $result[$callingCode][] = $recipient;
        }

        return $result;
    }
}
