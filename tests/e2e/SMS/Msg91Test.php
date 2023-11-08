<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Msg91;
use Utopia\Messaging\Messages\SMS;

class Msg91Test extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $sender = new Msg91(getenv('MSG_91_SENDER_ID'), getenv('MSG_91_AUTH_KEY'));

        $message = new SMS(
            to: [getenv('MSG_91_TO')],
            content: 'Test Content',
            from: getenv('MSG_91_FROM')
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertEquals('success', $result['type']);
    }
}
