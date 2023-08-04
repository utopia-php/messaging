<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Twilio;
use Utopia\Messaging\Messages\SMS;

class TwilioTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $sender = new Twilio('AC902ede6fda93fb923cc2c3128a2b79bd', 'cfc931ba5b7c1a3878ef5696938c8afc');

        $message = new SMS(
            to: ['+18034041123'],
            content: 'Test Content',
            from: '+15005550006'
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
