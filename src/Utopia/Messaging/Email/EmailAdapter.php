<?php

namespace Utopia\Messaging\Email;

use Utopia\Messaging\Message;
use Utopia\Messaging\Adapter;

abstract class EmailAdapter extends Adapter
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
        if (!($message instanceof EmailMessage)) {
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
     * @param EmailMessage $message Message to send.
     * @return string The response body.
     */
    abstract protected function sendMessage(EmailMessage $message): string;
}
