<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Push;

abstract class Base extends Adapter
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
        if (!($message instanceof Push)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->sendMessage($message);
    }

    abstract protected function sendMessage(Push $message): string;
}
