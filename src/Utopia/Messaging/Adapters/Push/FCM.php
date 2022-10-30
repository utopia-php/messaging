<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class FCM extends PushAdapter
{
    /**
     * @param  string  $serverKey The FCM server key.
     */
    public function __construct(
        private string $serverKey,
    ) {
    }

    public function getName(): string
    {
        return 'FCM';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Push $message): string
    {
        return $this->request(
            method: 'POST',
            url: 'https://fcm.googleapis.com/fcm/send',
            headers: [
                'Content-Type: application/json',
                "Authorization: key={$this->serverKey}",
            ],
            body: \json_encode([
                'registration_ids' => $message->getTo(),
                'notification' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                    'click_action' => $message->getAction(),
                    'icon' => $message->getIcon(),
                    'badge' => $message->getBadge(),
                    'color' => $message->getColor(),
                    'sound' => $message->getSound(),
                    'tag' => $message->getTag(),
                ],
                'data' => $message->getData(),
            ])
        );
    }
}
