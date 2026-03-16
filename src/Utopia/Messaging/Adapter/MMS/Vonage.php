<?php

namespace Utopia\Messaging\Adapter\MMS;

use Utopia\Messaging\Adapter\MMS as MMSAdapter;
use Utopia\Messaging\Adapter\VonageTrait;
use Utopia\Messaging\Messages\SMS as SMSMessage;

class Vonage extends MMSAdapter
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
        return $this->processMessage($message, 'mms');
    }
}
