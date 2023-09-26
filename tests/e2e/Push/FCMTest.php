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

        $response = $adapter->send($message);
        $response = \json_decode($response);

        $this->assertNotEmpty($response);
        // $this->assertEquals(200, $response['statusCode']);
    }

    public function testSendBenchmark(): void
    {
        $serverKey = getenv('FCM_SERVER_KEY');

        $adapter = new FCMAdapter($serverKey);

        $to = [];

        for($i = 0; $i < 5000; $i++) {
            $to[] = getenv('FCM_SERVER_TO');
        }

        $message = new Push(
            to: $to,
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

        $start = microtime(true);

        $adapter->send($message);

        $time = floor((microtime(true) - $start) * 1000);

        echo "\nFCMTest: $time ms\n";
        $this->assertGreaterThan(0, $time);
    }
}
