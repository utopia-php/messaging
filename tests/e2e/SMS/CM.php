<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\CM;
use Utopia\Messaging\Messages\SMS;

class CMest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $apiKey = getenv('CM_API_KEY');

        $to = [getenv('CM_TO')];
        $from = getenv('CM_FROM');

        $sender = new CM($apiKey);
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
