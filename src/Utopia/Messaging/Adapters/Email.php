<?php

namespace Utopia\Messaging\Adapters;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Email as EmailMessage;

abstract class Email extends Adapter
{
    public function getType(): string
    {
        return 'email';
    }

    public function getMessageType(): string
    {
        return EmailMessage::class;
    }

    /**
     * @inheritdoc
     * @param EmailMessage $message Message to send.
     * @throws \Exception
     */
    public function send(Message $message): string
    {
        if (!\is_a($message, $this->getMessageType())) {
            throw new \Exception('Invalid message type.');
        }
        if (\count($message->$this->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        return $this->process($message);
    }

    /**
     * Process an email message.
     * @param EmailMessage $message Message to process.
     * @return string The response body.
     */
    abstract protected function process(EmailMessage $message): string;
}
