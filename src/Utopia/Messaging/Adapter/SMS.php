<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
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
     * Send an SMS message.
     *
     * @param  SMSMessage  $message Message to send.
     * @return string The response body.
     *
     * @throws \Exception If the message fails.
     */
    abstract protected function process(SMSMessage $message): string;
}
