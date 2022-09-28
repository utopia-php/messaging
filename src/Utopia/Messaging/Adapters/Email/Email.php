<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

abstract class Email extends Adapter
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
        if (!($message instanceof \Utopia\Messaging\Messages\Email)) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->process($message);
    }

    /**
     * Process an email message.
     *
     * @param \Utopia\Messaging\Messages\Email $message Message to process.
     * @return string The response body.
     */
    abstract protected function process(\Utopia\Messaging\Messages\Email $message): string;
}
