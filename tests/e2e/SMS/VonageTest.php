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
        $apiKey = "9bae1fbc";
        $apiSecret = "thVmE4JpL4sPu4f6";

        $sender = new Vonage($apiKey, $apiSecret);

        $message = new SMS(
            to: ['+18034041123'],
            content: 'Test Content',
            from: '+12082740872'
        );

        $result = \json_decode($sender->send($message), true);

        $this->assertArrayHasKey("messages", $result);
        $this->assertEquals(1, count($result['messages']));
        $this->assertEquals("1", $result['message-count']);
    }
}
