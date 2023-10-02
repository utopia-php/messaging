<?php

namespace Utopia\Messaging\Adapters\Push;

use Exception;
use JsonException;
use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push as PushMessage;

class Pushwoosh extends PushAdapter
{
    protected const SEND_MESSAGE_URL = 'https://api.pushwoosh.com/json/1.3/createMessage';

    /**
     * @param string $applicationId Pushwoosh application ID.
     * @param string $authKey Pushwoosh auth key.
     */
    public function __construct(
        private string $applicationId,
        private string $authKey,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Pushwoosh';
    }

    /**
     * @inheritdoc
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     * @throws JsonException
     */
    protected function process(PushMessage $message): string
    {
        return $this->request(
            method: 'POST',
            url: static::SEND_MESSAGE_URL,
            body: \json_encode(
                ['request' => $this->buildRequest($message)],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    /**
     * @param PushMessage $message
     * @return array<string, mixed>
     */
    protected function buildRequest(PushMessage $message): array
    {
        $request = [
            'application' => $this->applicationId,
            'auth' => $this->authKey,
            'notifications' => [],
        ];

        $notification = [
            'send_date' => 'now',
            'ignore_user_timezone' => true,
            'title' => $message->getTitle(),
            'platforms' => [1,3,5,7,8,9,10,11,12,13,17],
            'content' => $message->getBody(),
            'wns_content' => base64_encode($message->getBody()),
            'to' => $message->getTo(),
            'data' => $message->getData() ?? [],
        ];

        if ($message->getSound() !== null) {
            $notification['ios_sound'] = "sound {$message->getSound()}";
            $notification['android_sound'] = $message->getSound();
        }

        if ($message->getIcon() !== null) {
            $notification['android_icon'] = 'android_icon';
        }

        if ($message->getBadge() !== null) {
            $notification['ios_badges'] = $message->getBadge();
            $notification['android_badges'] = $message->getBadge();
        }

        $request['notifications'][] = $notification;

        return $request;
    }
}
