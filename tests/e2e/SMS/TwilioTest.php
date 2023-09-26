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
        $sender = new Twilio(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN'));
        $to = [getenv('TWILIO_TO')];
        $from = getenv('TWILIO_FROM');

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = \json_decode($sender->send($message));

        $this->assertNotEmpty($result);
        $this->assertEquals($to[0], $result->to);
        $this->assertEquals($from, $result->from);
        $this->assertNull($result->error_message);
    }

    public function testSendSMSBenchmark()
    {
        $sender = new Twilio(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN'));
        $to = [getenv('TWILIO_TO')];
        $from = getenv('TWILIO_FROM');

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $sender->send($message);
        }

        $end = microtime(true);

        $time = floor(($end - $start) * 1000);

        echo "\nTwilioTest: $time ms\n";
        $this->assertGreaterThan(0, $time);

    }
}
