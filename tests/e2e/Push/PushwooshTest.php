<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\Pushwoosh;
use Utopia\Messaging\Messages\Push;

class PushwooshTest extends Base
{
    public function testSend(): void
    {
        $applicationId = getenv('PUSHUWOOSH_APP_ID');
        $authKey = getenv('PUSHWOOSH_AUTH_KEY');

        $adapter = new Pushwoosh($applicationId, $authKey);

        $to = getenv('PUSHUWOOSH_TO_TOKEN');

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
        $this->assertEquals(200, $response->status_code);
        $this->assertEquals('OK', $response->status_message);
        $this->assertNotEmpty($response->response);
        $this->assertCount(1, $response->response->Messages);
    }
}
