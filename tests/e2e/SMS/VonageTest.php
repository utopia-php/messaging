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

        $result = \json_decode($sender->send($message), true);

        $this->assertArrayHasKey('messages', $result);
        $this->assertEquals(1, count($result['messages']));
        $this->assertEquals('1', $result['message-count']);
    }
}
