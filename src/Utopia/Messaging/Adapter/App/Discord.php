<?php

namespace Utopia\Messaging\Adapter\App;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Discord as DiscordMessage;

class Discord extends Adapter
{
    /**
     * @param  string  $webhookId Your Discord webhook ID.
     * @param  string  $webhookToken Your Discord webhook token.
     */
    public function __construct(
        private string $webhookId,
        private string $webhookToken,
    ) {
    }

    public function getName(): string
    {
        return 'Discord';
    }

    public function getType(): string
    {
        return 'app';
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
        $query = [];

        if (! \is_null($message->getWait())) {
            $query['wait'] = $message->getWait();
        }
        if (! \is_null($message->getThreadId())) {
            $query['thread_id'] = $message->getThreadId();
        }

        $queryString = '';
        foreach ($query as $key => $value) {
            if (empty($queryString)) {
                $queryString .= '?';
            }
            $queryString .= $key.'='.$value;
        }

        return $this->request(
            method: 'POST',
            url: "https://discord.com/api/webhooks/{$this->webhookId}/{$this->webhookToken}{$queryString}",
            headers: [
                'Content-Type: application/json',
            ],
            body: \json_encode([
                'content' => $message->getContent(),
                'username' => $message->getUsername(),
                'avatar_url' => $message->getAvatarUrl(),
                'tts' => $message->getTTS(),
                'embeds' => $message->getEmbeds(),
                'allowed_mentions' => $message->getAllowedMentions(),
                'components' => $message->getComponents(),
                'attachments' => $message->getAttachments(),
                'flags' => $message->getFlags(),
                'thread_name' => $message->getThreadName(),
            ]),
        );
    }
}
