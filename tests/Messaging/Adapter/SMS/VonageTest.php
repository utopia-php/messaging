<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Vonage;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class VonageTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $apiKey = \getenv('VONAGE_API_KEY');
        $apiSecret = \getenv('VONAGE_API_SECRET');

        $sender = new Vonage($apiKey, $apiSecret);

        $message = new SMS(
            to: [\getenv('VONAGE_TO')],
            content: 'Test Content',
            from: \getenv('VONAGE_FROM')
        );

        $response = $sender->send($message);

        $result = \json_decode($response, true);

        $this->assertArrayHasKey('messages', $result);
        $this->assertEquals(1, count($result['messages']));
        $this->assertEquals('1', $result['message-count']);
    }
}
