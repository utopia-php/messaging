<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Priority;
use Utopia\Messaging\Response;

class OneSignal extends PushAdapter
{
    protected const NAME = 'OneSignal';

    /**
     * @param string $appId OneSignal App ID
     * @param string $apiKey OneSignal REST API Key
     */
    public function __construct(
        private string $appId,
        private string $apiKey,
    ) {
        parent::__construct();
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 2000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(PushMessage $message): array
    {
        $payload = [
            'app_id' => $this->appId,
            'include_player_ids' => $message->getTo(),
        ];

        if (!\is_null($message->getTitle())) {
            $payload['headings'] = ['en' => $message->getTitle()];
        }
        if (!\is_null($message->getBody())) {
            $payload['contents'] = ['en' => $message->getBody()];
        }
        if (!\is_null($message->getData())) {
            $payload['data'] = $message->getData();
        }
        if (!\is_null($message->getAction())) {
            $payload['url'] = $message->getAction();
        }
        if (!\is_null($message->getImage())) {
            $payload['big_picture'] = $message->getImage();
            $payload['ios_attachments'] = ['id' => $message->getImage()];
        }
        if (!\is_null($message->getIcon())) {
            $payload['small_icon'] = $message->getIcon();
        }
        if (!\is_null($message->getColor())) {
            $payload['android_accent_color'] = $message->getColor();
        }
        if (!\is_null($message->getTag())) {
            $payload['android_group'] = $message->getTag();
        }
        if (!\is_null($message->getSound())) {
            $payload['android_sound'] = $message->getSound();
            $payload['ios_sound'] = $message->getSound() . '.wav';
        }
        if (!\is_null($message->getBadge())) {
            $payload['ios_badgeType'] = 'SetTo';
            $payload['ios_badgeCount'] = $message->getBadge();
        }
        if (!\is_null($message->getContentAvailable())) {
            $payload['content_available'] = true;
        }
        if (!\is_null($message->getPriority())) {
            $payload['priority'] = match ($message->getPriority()) {
                Priority::HIGH => 10,
                Priority::NORMAL => 5,
            };
        }

        $results = $this->request(
            method: 'POST',
            url: 'https://onesignal.com/api/v1/notifications',
            headers: [
                'Content-Type: application/json',
                "Authorization: Basic {$this->apiKey}",
            ],
            body: $payload
        );

        $response = new Response($this->getType());

        if ($results['statusCode'] >= 200 && $results['statusCode'] < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResult($to, $results['response']['errors'][0] ?? 'Unknown error');
            }
        }

        return $response->toArray();
    }
}
