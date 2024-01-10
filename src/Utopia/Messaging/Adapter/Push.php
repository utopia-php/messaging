<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Push as PushMessage;

abstract class Push extends Adapter
{
    private const TYPE = 'push';
    private const MESSAGE_TYPE = PushMessage::class;
    private const EXPIRED_MESSAGE = 'Expired device token.';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessageType(): string
    {
        return self::MESSAGE_TYPE;
    }

    protected function getExpiredErrorMessage(): string
    {
        return self::EXPIRED_MESSAGE;
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
