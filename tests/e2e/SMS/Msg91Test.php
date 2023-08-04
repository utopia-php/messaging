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
        $sender = new Msg91('12345', '402985Ajm8DXo3EG4964cd3c10P1');

        $message = new SMS(
            to: ['+18034041123'],
            content: 'Test Content',
            from: '+15005550006'
        );

        $result = \json_decode($sender->send($message), true);

        $this->assertEquals('success', $result["type"]);
    }
}
