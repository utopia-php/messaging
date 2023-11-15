<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\SMSApi;
use Utopia\Messaging\Messages\SMS;

class SMSApiTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        // $sender = new SMSApi(getenv('SMSAPI_AUTH_TOKEN'));

        // $message = new SMS(
        //     to: [getenv('SMSAPI_TO')],
        //     content: 'Test Content',
        //     from: getenv('SMSAPI_FROM')
        // );

        // $response = $sender->send($message);
        // $result = \json_decode($response, true);

        // $this->assertEquals('success', $result['type']);

        $this->markTestSkipped('SMSApi currenlty not available in INDIA.');
    }
}
