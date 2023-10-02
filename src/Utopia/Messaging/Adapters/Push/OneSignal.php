<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class OneSignal extends PushAdapter
{
    /**
     * @param  string  $authKey
     */
    public function __construct(
        private string $authKey,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'OneSignal';
    }

    /**
     * Get max messages per request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 6000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Push $message): string
    {
        return $this->request(
            
        );
    }
}