<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class Pusher extends PushAdapter
{
    /**
     * @param  string  $instanceId The unique identifier for Pusher Beams instance.
     * @param  string  $secretKey The secret key to access Pusher Beams instance.
     */
    public function __construct(
        private string $instanceId,
        private string $secretKey,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Pusher';
    }

    /**
     * Get max messages per request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    private function removeNullFields(array $object): array
    {
        $output = [];
        foreach ($object as $key => $val) {
            if (is_array($val) && array_keys($val) !== range(0, count($val) - 1)) {
                $output[$key] = $this->removeNullFields($val);
            } elseif (! is_null($val)) {
                $output[$key] = $val;
            }
        }

        return $output;
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
            url: "https://{$this->instanceId}.pushnotifications.pusher.com/publish_api/v1/instances/{$this->instanceId}/publishes/users",
            headers: [
                'Content-Type: application/json',
                "Authorization: Bearer {$this->secretKey}",
            ],
            body: \json_encode($this->removeNullFields([
                'users' => $message->getTo(),
                'apns' => [
                    'aps' => [
                        'alert' => [
                            'title' => $message->getTitle(),
                            'body' => $message->getBody(),
                        ],
                        'badge' => $message->getBadge(),
                        'sound' => $message->getSound(),
                        'data' => $message->getData(),
                    ],
                ],
                'fcm' => [
                    'notification' => [
                        'title' => $message->getTitle(),
                        'body' => $message->getBody(),
                        'click_action' => $message->getAction(),
                        'icon' => $message->getIcon(),
                        'color' => $message->getColor(),
                        'sound' => $message->getSound(),
                        'tag' => $message->getTag(),
                    ],
                    'data' => $message->getData(),
                ],
                'web' => [
                    'notification' => [
                        'title' => $message->getTitle(),
                        'body' => $message->getBody(),
                        'icon' => $message->getIcon(),
                    ],
                    'data' => $message->getData(),
                ],
            ]))
        );
    }
}
