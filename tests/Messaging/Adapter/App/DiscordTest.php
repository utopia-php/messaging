<?php

namespace Utopia\Tests\Adapter\App;

use Utopia\Tests\Adapter\Base;
use Utopia\Messaging\Adapter\App\Discord;
use Utopia\Messaging\Messages\Discord as DiscordMessage;

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