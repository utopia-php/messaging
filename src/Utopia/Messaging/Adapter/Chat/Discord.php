<?php

namespace Utopia\Messaging\Adapter\Chat;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Discord as DiscordMessage;
use Utopia\Messaging\Response;

class Discord extends Adapter
{
    private const NAME = 'Discord';
    private const TYPE = 'chat';
    private const MESSAGE_TYPE = DiscordMessage::class;

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
        return self::NAME;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessageType(): string
    {
        return self::MESSAGE_TYPE;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception
     */
    protected function process(DiscordMessage $message): array
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

        $response = new Response($this->getType());
        $result = $this->request(
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

        $statusCode = $result['statusCode'];

        if ($statusCode >= 200 && $statusCode < 300) {
            $response->setDeliveredTo(1);
            $response->addResultForRecipient($this->webhookId);
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $response->addResultForRecipient($this->webhookId, 'Bad Request.');
        } else {
            $response->addResultForRecipient($this->webhookId, 'Unknown Error.');
        }

        return $response->toArray();
    }
}
