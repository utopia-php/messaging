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

        var_dump($key);
        var_dump($domain);
        exit;

        $sender = new Mailgun(
            $key,
            $domain
        );

        $to = 'wcope@me.com';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $sender->send($message);

        $this->assertEquals(true, true);
    }
}
