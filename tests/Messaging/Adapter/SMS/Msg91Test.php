<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Msg91;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class Msg91Test extends Base
{
    public function testSendSMS(): void
    {
        $sender = new Msg91(getenv('MSG_91_SENDER_ID'), getenv('MSG_91_AUTH_KEY'), getenv('MSG_91_TEMPLATE_ID'));

        $message = new SMS(
            to: [getenv('MSG_91_TO')],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
