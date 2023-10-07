<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\Pushbullet as PushbulletAdapter;
use Utopia\Messaging\Messages\Push;

class PushBulletTest extends Base
{
    public function testSend(): void
    {
        $pushbulletApiKey = getenv('PUSHBULLET_API_KEY');

        $adapter = new PushbulletAdapter($pushbulletApiKey);

        $message = new Push(
            to: [$to],
            title: 'TestTitle',
            body: 'TestBody',
            data: null,
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: '1'
        );

        $response = \json_decode($adapter->process($message));

        $this->assertNotEmpty($response);
        $this->assertEquals('success', $response->status);
    }
}
