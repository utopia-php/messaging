<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Tests\Adapter\Base;

class Msg91Test extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        // $sender = new Msg91(\getenv('MSG_91_SENDER_ID'), \getenv('MSG_91_AUTH_KEY'));

        // $message = new SMS(
        //     to: [\getenv('MSG_91_TO')],
        //     content: 'Test Content',
        //     from: \getenv('MSG_91_FROM')
        // );

        // $response = $sender->send($message);
        // $result = \json_decode($response, true);

        // $this->assertEquals('success', $result['type']);

        $this->markTestSkipped('Msg91 requires business verification to use template and SMS api.');
    }
}
