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

        $message = new Push(
            ['eJa9AhokQUudfBPJwRx2OX:APA91bE0KbMkXU7a4eCyq1CyN1nR9TwOD5NQIaHADJBMBV1GjOjTfyPywOXKVeKVvvjz6nvB2jASGtRxGJHsM4Z4osoHnTx5IrnxCNUDEH11wsm4vMBiKW0zbugVis1MdtusTu9admrk'],
            'TestTitle',
            'TestBody',
            null,
            null,
            'default',
            null,
            null,
            null,
            '1'
        );

        $response = $adapter->send($message);

        $this->assertNotEmpty($response);
    }
}
