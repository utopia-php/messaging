<?php

namespace Utopia\Messaging\Adapter\Viber;

use Utopia\Messaging\Adapter\Viber as ViberAdapter;
use Utopia\Messaging\Adapter\VonageTrait;
use Utopia\Messaging\Messages\SMS as SMSMessage;

class Vonage extends ViberAdapter
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
        return $this->processMessage($message, 'viber');
    }
}
