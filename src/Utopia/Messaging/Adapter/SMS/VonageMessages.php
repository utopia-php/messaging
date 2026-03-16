<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Helpers\JWT;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class VonageMessages extends SMSAdapter
{
    use VonageTrait;

    protected const NAME = 'VonageMessages';

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
        return $this->processMessage($message, 'sms');
    }
}
