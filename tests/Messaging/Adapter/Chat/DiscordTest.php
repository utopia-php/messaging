<?php

namespace Utopia\Tests\Adapter\Chat;

use Utopia\Messaging\Adapter\Chat\Discord;
use Utopia\Messaging\Messages\Discord as DiscordMessage;
use Utopia\Tests\Adapter\Base;

class DiscordTest extends Base
{
    public function testSendMessage(): void
    {
        $id = \getenv('DISCORD_WEBHOOK_ID');
        $token = \getenv('DISCORD_WEBHOOK_TOKEN');

        $sender = new Discord(
            webhookId: $id,
            webhookToken: $token
        );

        $content = 'Test Content';

        $message = new DiscordMessage(
            content: $content,
            wait: true
        );

        $result = \json_decode($sender->send($message), true);

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result['id']);
    }
}
