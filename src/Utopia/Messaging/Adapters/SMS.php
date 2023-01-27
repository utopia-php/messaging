<?php

namespace Utopia\Messaging\Adapters;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\SMS as SMSMessage;

abstract class SMS extends Adapter
{
    public function getType(): string
    {
        return 'sms';
    }

    public function getMessageType(): string
    {
        return SMSMessage::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param Message $message Message to send.
     *
     * @throws \Exception
     */
    public function send(Message $message): string
    {
        if (! \is_a($message, $this->getMessageType())) {
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
     * @param  SMSMessage  $message Message to send.
     * @return string The response body.
     */
    abstract protected function process(SMSMessage $message): string;
}
