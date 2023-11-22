<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;
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
     *
     * @return string
     */
    public function getName(): string
    {
        return 'FCM';
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

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Push $message): string
    {
        $response = new Response(0, 0, $this->getType(), []);
        $result =  \json_decode($this->request(
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
        ), true);

        $response->setSuccess($result['response']['success']);
        $response->setFailure($result['response']['failure']);

        $details = $response->getDetails();

        foreach ($result['response']['results'] as $index => $item) {
            if ($result['statusCode'] === 200) {
                $details[] = [
                    'recipient' => $message->getTo()[$index],
                    'status' => \array_key_exists('error', $item) ? 'failure' : 'success',
                    'error' => \array_key_exists('error', $item) 
                                    ? match ($item['error']) {
                                        'MissingRegistration' => 'Bad Request. Missing token.',
                                        'InvalidRegistration' => 'Invalid token.',
                                        'NotRegistered' => 'Expired token.',
                                        'MessageTooBig' => 'Payload is too large. Please keep maximum 4096 bytes for messages.',
                                        'DeviceMessageRateExceeded' => 'Too many requests were made consecutively to the same device token.',
                                        'InternalServerError' => 'Internal server error.',
                                        default => $item['error'],
                                    } : '',
                ];
            } else if ($result['statusCode'] === 400) {
                $details[] = [
                    'recipient' => $message->getTo()[$index],
                    'status' => 'failure',
                    'error' => match ($item['error']) {
                        'Invalid JSON' => 'Bad Request.',
                        'Invalid Parameters' => 'Bad Request.',
                    }
                ]; 
            } else if ($result['statusCode'] === 401) {
                $details[] = [
                    'recipient' => $message->getTo()[$index],
                    'status' => 'failure',
                    'error' => 'Authentication error.',
                ];
            } else if ($result['statusCode'] >= 500) {
                $details[] = [
                    'recipient' => $message->getTo()[$index],
                    'status' => 'failure',
                    'error' => 'Server unavailable.',
                ];
            } else {
                $details[] = [
                    'recipient' => $message->getTo()[$index],
                    'status' => 'failure',
                    'error' => 'Unknown error',
                ];
            }
           
        }

        $response->setDetails($details);

        return \json_encode($response->toArray());
    }
}
