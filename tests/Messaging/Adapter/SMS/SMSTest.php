<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Mock;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SMSTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $sender = new Mock('username', 'password');

        $message = new SMS(
            to: ['+123456789'],
            content: 'Test Content',
            from: '+987654321'
        );

        $sender->send($message);

        $smsRequest = $this->getLastRequest();

        $this->assertSame('http://request-catcher:5000/mock-sms', $smsRequest['url']);
        $this->assertSame('Appwrite Mock Message Sender', $smsRequest['headers']['User-Agent']);
        $this->assertSame('username', $smsRequest['headers']['X-Username']);
        $this->assertSame('password', $smsRequest['headers']['X-Key']);
        $this->assertSame('POST', $smsRequest['method']);
        $this->assertSame('+987654321', $smsRequest['data']['from']);
        $this->assertSame('+123456789', $smsRequest['data']['to']);
        $this->assertSame(98, $sender->getCountryCode($smsRequest['data']['from']));
        $this->assertSame(1, $sender->getCountryCode($smsRequest['data']['to']));
    }
}
