<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\Pushy as PushyAdapter;
use Utopia\Messaging\Messages\Push;

class PushyTest extends Base{
    public function testSend(): void {
        $secretKey = getenv('PUSHY_SECRET_KEY');

        $adapter = new PushyAdapter($secretKey);

        $to = getenv('PUSHY_SERVER_TO');

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
        $this->assertEquals(1, $response->success);
        $this->assertEquals(0, $response->failure);
    }
}