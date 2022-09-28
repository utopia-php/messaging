<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Email;

abstract class Base extends Adapter
{
    public function getType(): string
    {
        return 'email';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function send(Message $message): string
    {
        if (!($message instanceof Email)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->sendMessage($message);
    }

    /**
     * Send an email message.
     *
     * @param Email $message Message to send.
     * @return string The response body.
     */
    abstract protected function sendMessage(Email $message): string;
}
