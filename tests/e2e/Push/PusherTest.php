<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\Pusher as PusherAdapter;
use Utopia\Messaging\Messages\Push;

class PusherTest extends Base
{
    public function testSend(): void
    {
        $instanceId = getenv('PUSHER_INSTANCE_ID');
        $secretKey = getenv('PUSHER_SECRET_KEY');

        $adapter = new PusherAdapter($instanceId, $secretKey);

        $to = getenv('PUSHER_TO');

        $message = new Push(
            to: [$to],
            title: 'TestTitle',
            body: 'TestBody',
            data: [
                'some' => 'metadata',
            ],
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: '1'
        );

        $response = \json_decode($adapter->send($message));

        $this->assertNotEmpty($response);
        $this->assertIsString($response->publishId);
    }
}
