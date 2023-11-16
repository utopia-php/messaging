<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;

class SendgridTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        /*
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $response = $sender->send($message);

        $this->assertEquals($response, '');
        */

        $this->markTestSkipped('Sendgrid: Authenticated user is not authorized to send mail');
    }

    public function testSendEmailBenchmark()
    {
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $sender->send($message);
        }

        $end = microtime(true);

        $time = floor(($end - $start) * 1000);

        echo "\nSendgrid: $time ms\n";
        $this->assertGreaterThan(0, $time);
    }
}
