<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Mock;
use Utopia\Messaging\Messages\SMS;

class SMSTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $sender = new Mock('username', 'password');

        $message = new SMS(
            to: ['+123456789'],
            content: 'Test Content',
            from: '+987654321'
        );

        $sender->send($message);

        $smsRequest = $this->getLastRequest();

        $this->assertEquals('http://request-catcher:5000/mock-sms', $smsRequest['url']);
        $this->assertEquals('Appwrite Mock Message Sender', $smsRequest['headers']['User-Agent']);
        $this->assertEquals('username', $smsRequest['headers']['X-Username']);
        $this->assertEquals('password', $smsRequest['headers']['X-Key']);
        $this->assertEquals('POST', $smsRequest['method']);
        $this->assertEquals('+987654321', $smsRequest['data']['from']);
        $this->assertEquals('+123456789', $smsRequest['data']['to']);
    }
}
