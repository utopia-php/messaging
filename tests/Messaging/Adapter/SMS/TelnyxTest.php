<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Tests\Adapter\Base;

class TelnyxTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        // $sender = new Telnyx(\getenv('TELNYX_API_KEY'));

        // $message = new SMS(
        //     to: ['+18034041123'],
        //     content: 'Test Content',
        //     from: '+15005550006'
        // );

        // $result = $sender->send($message);

        // $this->assertEquals('success', $result["type"]);

        $this->markTestSkipped('Telnyx had no testing numbers available at this time.');
    }
}
