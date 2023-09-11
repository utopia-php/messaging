<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\FCM as FCMAdapter;
use Utopia\Messaging\Messages\Push;

class FCMTest extends Base
{
    public function testSend(): void
    {
        $serverKey = getenv('FCM_SERVER_KEY');

        $adapter = new FCMAdapter($serverKey);

        $to = "eJa9AhokQUudfBPJwRx2OX:APA91bE0KbMkXU7a4eCyq1CyN1nR9TwOD5NQIaHADJBMBV1GjOjTfyPywOXKVeKVvvjz6nvB2jASGtRxGJHsM4Z4osoHnTx5IrnxCNUDEH11wsm4vMBiKW0zbugVis1MdtusTu9admrk"; //getenv('FCM_SERVER_TO');

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
