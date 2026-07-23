<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\SMSGateApp;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SMSGateAppTest extends Base
{
    public function testSendSMS(): void
    {
        $username = \getenv('SMSGATEAPP_USERNAME');
        $password = \getenv('SMSGATEAPP_PASSWORD');
        $to = \getenv('SMSGATEAPP_TO');

        if (!$username || !$password || !$to) {
            $this->markTestSkipped('SMSGateApp credentials not configured');
        }

        $endpoint = \getenv('SMSGATEAPP_ENDPOINT') ?: null;

        $sender = new SMSGateApp($username, $password, $endpoint);

        $message = new SMS(
            to: [$to],
            content: 'Test content from SMSGateApp'
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
