<?php

namespace Tests\E2E\Email;

use Tests\E2E\Base;
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

        $response = (array) \json_decode($sender->send($message), true);

        $this->assertEquals(1, $response['success']);
        $this->assertEquals(0, $response['failure']);
        $this->assertEquals([], $response['details']);
    }
}
