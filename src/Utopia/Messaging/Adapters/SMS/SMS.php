<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

abstract class SMS extends Adapter
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
        if (!($message instanceof \Utopia\Messaging\Messages\SMS)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->process($message);
    }

    /**
     * Send an SMS message.
     *
     * @param \Utopia\Messaging\Messages\SMS $message Message to send.
     * @return string The response body.
     */
    abstract protected function process(\Utopia\Messaging\Messages\SMS $message): string;
}
