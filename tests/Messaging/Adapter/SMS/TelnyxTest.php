<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Telnyx;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class TelnyxTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $sender = new Telnyx(\getenv('TELNYX_API_KEY'));

        $message = new SMS(
            to: [\getenv('TELNYX_TO')],
            content: 'Test Content',
            from: \getenv('TELNYX_FROM')
        );

        $result = $sender->send($message);

        $this->assertResponse($result);
    }
}
