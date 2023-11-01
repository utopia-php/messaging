<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Tests\Adapter\Base;
use Utopia\Messaging\Adapter\Email\Mailgun;
use Utopia\Messaging\Messages\Email;

class MailgunTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail(): void
    {
        $key = \getenv('MAILGUN_API_KEY');
        $domain = \getenv('MAILGUN_DOMAIN');

        $sender = new Mailgun(
            apiKey: $key,
            domain: $domain,
            isEU: false
        );

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from,
        );

        $result = \json_decode($sender->send($message), true);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue(\str_contains(\strtolower($result['message']), 'queued'));
    }
}
