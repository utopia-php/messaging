<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Response;

class FCM extends PushAdapter
{
    /**
     * @param  string  $serverKey The FCM server key.
     */
    public function __construct(
        private string $serverKey,
    ) {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return 'FCM';
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(PushMessage $message): string
    {
        $response = new Response($this->getType());
        $result = $this->request(
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

        $response->setDeliveredTo($result['response']['success']);

        foreach ($result['response']['results'] as $index => $item) {
            if ($result['statusCode'] === 200) {
                $response->addToDetails(
                    $message->getTo()[$index],
                    \array_key_exists('error', $item)
                                    ? match ($item['error']) {
                                        'MissingRegistration' => 'Bad Request. Missing token.',
                                        'InvalidRegistration' => 'Invalid token.',
                                        'NotRegistered' => 'Expired token.',
                                        'MessageTooBig' => 'Payload is too large. Please keep maximum 4096 bytes for messages.',
                                        'DeviceMessageRateExceeded' => 'Too many requests were made consecutively to the same device token.',
                                        'InternalServerError' => 'Internal server error.',
                                        default => $item['error'],
                                    } : '',
                );
            } elseif ($result['statusCode'] === 400) {
                $response->addToDetails(
                    $message->getTo()[$index],
                    match ($item['error']) {
                        'Invalid JSON' => 'Bad Request.',
                        'Invalid Parameters' => 'Bad Request.',
                        'default' => null,
                    },
                );
            } elseif ($result['statusCode'] === 401) {
                $response->addToDetails(
                    $message->getTo()[$index],
                    'Authentication error.',
                );
            } elseif ($result['statusCode'] >= 500) {
                $response->addToDetails(
                    $message->getTo()[$index],
                    'Server unavailable.',
                );
            } else {
                $response->addToDetails(
                    $message->getTo()[$index],
                    'Unknown error',
                );
            }

        }

        return \json_encode($response->toArray());
    }
}
