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

    protected static function getExpiredErrorMessage(): string
    {
        return 'Expired device token.';
    }

    /**
     * Send a push message.
     *
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception
     */
    abstract protected function process(PushMessage $message): array;
}
