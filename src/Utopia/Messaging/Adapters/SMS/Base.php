<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\Adapter;
use Utopia\Messaging\Messages\Message;
use Utopia\Messaging\Messages\SMS;

abstract class Base extends Adapter
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
        if (!($message instanceof SMS)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->sendMessage($message);
    }

    abstract protected function sendMessage(SMS $message): string;
}
