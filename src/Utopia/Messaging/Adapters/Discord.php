<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Discord as DiscordMessage;

class Discord extends Adapter
{
    /**
     * @param string $webhookId Your Discord webhook ID.
     * @param string $webhookToken Your Discord webhook token.
     */
    public function __construct(
        private string $webhookId,
        private string $webhookToken,
    )
    {
    }

    public function getName(): string
    {
        return 'Discord';
    }

    public function getType(): string
    {
        return 'external';
    }

    public function getMessageType(): string
    {
        return DiscordMessage::class;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * @throws \Exception
     */
    protected function process(DiscordMessage $message): string
    {
        return $this->request(
            method: 'POST',
            url: "https://discord.com/api/webhooks/{$this->webhookId}/{$this->webhookToken}",
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'content' => $message->getContent(),
                'username' => $message->getUsername(),
                'avatar_url' => $message->getAvatarUrl(),
            ]),
        );
    }
}
