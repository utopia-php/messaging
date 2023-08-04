<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Telesign;
use Utopia\Messaging\Messages\SMS;

class TelesignTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
    //     $username = '';
    //     $password = '';

    //     $sender = new Telesign($username, $password);

    //     $message = new SMS(
    //         to: ['+18034041123'],
    //         content: 'Test Content',
    //         from: '+15005550006'
    //     );

    //     $result = \json_decode($sender->send($message), true);

    //     $this->assertEquals('success', $result["type"]);

        $this->markTestSkipped('Telesign requires sales calls and such to setup an account');
    }
}
