<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Vonage;
use Utopia\Messaging\Messages\SMS;

class VonageTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $apiKey = getenv('VONAGE_API_KEY');
        $apiSecret = getenv('VONAGE_API_SECRET');

        $sender = new Vonage($apiKey, $apiSecret);

        $message = new SMS(
            to: [getenv('VONAGE_TO')],
            content: 'Test Content',
            from: getenv('VONAGE_FROM')
        );

        $response = $sender->send($message);

        $result = \json_decode($response, true);

        $this->assertArrayHasKey('messages', $result);
        $this->assertEquals(1, count($result['messages']));
        $this->assertEquals('1', $result['message-count']);
    }

    public function testSendSMSBenchmark()
    {
        $apiKey = getenv('VONAGE_API_KEY');
        $apiSecret = getenv('VONAGE_API_SECRET');

        $sender = new Vonage($apiKey, $apiSecret);

        $message = new SMS(
            to: [getenv('VONAGE_TO')],
            content: 'Test Content',
            from: getenv('VONAGE_FROM')
        );

        $start = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            $sender->send($message);
        }

        $end = microtime(true);

        $time = floor(($end - $start) * 1000);

        echo "\nVonageTest: $time ms\n";
        $this->assertGreaterThan(0, $time);
    }
}
