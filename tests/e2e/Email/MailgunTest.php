<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mailgun;
use Utopia\Messaging\Messages\Email;

class MailgunTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('MAILGUN_API_KEY');
        $domain = getenv('MAILGUN_DOMAIN');

        $sender = new Mailgun(
            apiKey: $key,
            domain: $domain,
            isEU: false
        );

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $result = (array) \json_decode($sender->send($message));

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue(str_contains(strtolower($result['message']), 'queued'));
    }

    public function testSendEmailBenchmark()
    {
        $key = getenv('MAILGUN_API_KEY');
        $domain = getenv('MAILGUN_DOMAIN');

        $sender = new Mailgun(
            apiKey: $key,
            domain: $domain,
            isEU: false
        );

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@'.$domain;

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

        echo "\nMailgun: $time ms\n";
        $this->assertGreaterThan(0, $time);
    }
}
