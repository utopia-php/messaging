<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\FCM as FCMAdapter;
use Utopia\Messaging\Messages\Push;
use Utopia\Tests\Adapter\Base;

class FCMTest extends Base
{
    public function testSend(): void
    {
        $serverKey = \getenv('FCM_SERVER_KEY');

        $adapter = new FCMAdapter($serverKey);

        $to = \getenv('FCM_SERVER_TO');

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

        $response = \json_decode($adapter->send($message));

        $this->assertNotEmpty($response);
        $this->assertEquals(1, $response->success);
        $this->assertEquals(0, $response->failure);
    }
}
