<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Termii;
use Utopia\Messaging\Messages\SMS;

class TermiiTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $apiKey = getenv('TERMII_API_KEY');

        $to = [getenv('TERMII_TO')];
        $from = getenv('TERMII_FROM');

        $sender = new Termii($apiKey);
        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('message_id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('balance', $result);
    }
}
