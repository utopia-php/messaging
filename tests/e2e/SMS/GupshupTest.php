<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Gupshup;
use Utopia\Messaging\Messages\SMS;

class GupshupTest extends Base
{
    public function testSendSMS()
    {
        $apiKey = getenv('GUPSHUP_API_KEY');
        $to = getenv('GUPSHUP_TO');
        $from = getenv('GUPSHUP_FROM');

        $gupshup = new Gupshup($apiKey);
        $message = new SMS(
            to: $to,
            content: 'Test SMS Message',
            from: $from
        );

        $response = $gupshup->send($message);
        $result = json_decode($response, true);

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('message_id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('balance', $result);
    }
}
