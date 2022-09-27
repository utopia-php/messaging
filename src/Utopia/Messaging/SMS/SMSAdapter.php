<?php

namespace Utopia\Messaging\SMS;

use Utopia\Messaging\Message;
use Utopia\Messaging\Adapter;

abstract class SMSAdapter extends Adapter
{
    public function getType(): string
    {
        return 'sms';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function send(Message $message): string
    {
        if (!($message instanceof SMSMessage)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->sendMessage($message);
    }

    abstract protected function sendMessage(SMSMessage $message): string;
}
