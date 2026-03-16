<?php

namespace Utopia\Messaging\Adapter\WhatsApp;

use Utopia\Messaging\Adapter\WhatsApp as WhatsAppAdapter;
use Utopia\Messaging\Adapter\VonageTrait;
use Utopia\Messaging\Messages\SMS as SMSMessage;

class Vonage extends WhatsAppAdapter
{
    use VonageTrait;

    protected const NAME = 'Vonage';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function process(SMSMessage $message): array
    {
        return $this->processMessage($message, 'whatsapp');
    }
}
