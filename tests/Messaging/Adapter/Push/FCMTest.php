<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\FCM as FCMAdapter;
use Utopia\Messaging\Messages\Push;
use Utopia\Tests\Adapter\Base;

class FCMTest extends Base
{
    public function testSend(): void
    {
        $serverKey = \getenv('FCM_SERVICE_ACCOUNT_JSON');

        $adapter = new FCMAdapter($serverKey);

        $to = \getenv('FCM_SERVER_TO');

        $message = new Push(
            to: [$to],
            title: 'Test title',
            body: 'Test body',
            data: null,
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: 1
        );

        $response = $adapter->send($message);

        $this->assertResponse($response);
    }
}
