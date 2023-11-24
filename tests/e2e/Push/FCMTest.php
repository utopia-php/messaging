<?php

namespace Tests\E2E\Push;

use Tests\E2E\Base;
use Utopia\Messaging\Adapters\Push\FCM as FCMAdapter;
use Utopia\Messaging\Messages\Push;

class FCMTest extends Base
{
    public function testSend(): void
    {
        $serverKey = getenv('FCM_SERVER_KEY');

        $adapter = new FCMAdapter($serverKey);

        $to = getenv('FCM_SERVER_TO');

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

        $response = \json_decode($adapter->send($message), true);

        $this->assertResponse($response);
    }
}
