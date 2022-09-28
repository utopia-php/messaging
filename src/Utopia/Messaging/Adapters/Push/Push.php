<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

abstract class Push extends Adapter
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
        if (!($message instanceof \Utopia\Messaging\Messages\Push)) {
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
     * @param \Utopia\Messaging\Messages\Push $message Message to process.
     * @return string The response body.
     */
    abstract protected function process(\Utopia\Messaging\Messages\Push $message): string;
}
