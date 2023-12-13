<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
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
     * Send a push message.
     *
     * @param  PushMessage  $message Message to process.
     * @return string The response body.
     *
     * @throws \Exception If the message fails.
     */
    abstract protected function process(PushMessage $message): array;
}
