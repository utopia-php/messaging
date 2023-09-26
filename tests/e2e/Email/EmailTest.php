<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mock;
use Utopia\Messaging\Messages\Email;

class EmailTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $sender = new Mock();

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@localhost.test';

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($from, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }

    public function testSendEmailBenchmark()
    {
        $sender = new Mock();

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@localhost.test';

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        $sender->send($message);

        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $sender->send($message);
        }

        $end = microtime(true);

        $time = floor(($end - $start) * 1000);

        echo "\nEmail: $time ms\n";
        $this->assertGreaterThan(0, $time);
    }
}
