<?php

namespace Tests\E2E\SMS;

use Tests\E2E\Base;
use Utopia\Messaging\Adapters\SMS\Msg91;
use Utopia\Messaging\Messages\SMS;

class Msg91Test extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $sender = new Msg91(getenv('MSG_91_SENDER_ID'), getenv('MSG_91_AUTH_KEY'), getenv('MSG_91_TEMPLATE_ID'));

        $message = new SMS(
            to: [getenv('MSG_91_TO')],
            content: 'Test Content',
        );

        $response = \json_decode($sender->send($message), true);

        $this->assertResponse($response);
    }
}
