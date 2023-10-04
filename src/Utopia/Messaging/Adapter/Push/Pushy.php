<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Response;

class Pushy extends PushAdapter
{
    protected const NAME = 'Pushy';

    public function __construct(
        private readonly string $secretKey,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    protected function process(PushMessage $message): array
    {
        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: "https://api.pushy.me/push?api_key={$this->secretKey}",
            headers: [
                'Content-Type: application/json',
            ],
            body: [
                'to' => $message->getTo(),
                'data' => $message->getData() ?? [],
                'notification' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                    'badge' => $message->getBadge(),
                    'sound' => $message->getSound(),
                ],
            ],
        );

        $statusCode = $result['statusCode'];
        $responseBody = $result['response'];

        if ($statusCode >= 200 && $statusCode < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $error = '';
            if (\is_array($responseBody) && isset($responseBody['error'])) {
                $error = $responseBody['error'];
            } elseif (\is_string($responseBody)) {
                $error = $responseBody;
            }

            foreach ($message->getTo() as $to) {
                $response->addResult($to, $error ?: 'Unknown error');
            }
        }

        return $response->toArray();
    }
}
