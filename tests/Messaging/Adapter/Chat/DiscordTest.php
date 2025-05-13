<?php

namespace Utopia\Tests\Adapter\Chat;

use Utopia\Messaging\Adapter\Chat\Discord;
use Utopia\Messaging\Messages\Discord as DiscordMessage;
use Utopia\Tests\Adapter\Base;

class DiscordTest extends Base
{
    public function testSendMessage(): void
    {
        $url = \getenv('DISCORD_WEBHOOK_URL');

        $sender = new Discord($url);

        $content = 'Test Content';

        $message = new DiscordMessage(
            content: $content,
            wait: true
        );

        $result = $sender->send($message);

        $this->assertResponse($result);
    }
}
