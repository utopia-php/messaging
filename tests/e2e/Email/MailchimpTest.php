<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mailchimp;
use Utopia\Messaging\Messages\Email;

class MailchimpTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('MAILCHIMP_API_KEY');

        $sender = new Mailchimp(
            apiKey: $key,
        );

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

        $result = (array) \json_decode($sender->send($message))[0];

        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertTrue(str_contains(strtolower($result['status']), 'sent'));
    }
}
