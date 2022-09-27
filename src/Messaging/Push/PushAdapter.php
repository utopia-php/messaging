<?php

namespace Messaging\Push;

use Messaging\Adapter;
use Messaging\Message;

abstract class PushAdapter extends Adapter
{
    public function getType(): string
    {
        return 'push';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function send(Message $message): string
    {
        if (!($message instanceof PushMessage)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->sendMessage($message);
    }

    protected abstract function sendMessage(PushMessage $message): string;
}