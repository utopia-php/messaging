<?php

namespace Utopia\Messaging\Adapters;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Push as PushMessage;

abstract class Push extends Adapter
{
    public function getType(): string
    {
        return 'push';
    }

    public function getMessageType(): string
    {
        return PushMessage::class;
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
     * Send a push message.
     *
     * @param  PushMessage  $message Message to process.
     * @return string The response body.
     */
    abstract protected function process(PushMessage $message): string;
}
