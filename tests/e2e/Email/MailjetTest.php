<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mailjet;
use Utopia\Messaging\Messages\Email;

class MailjetTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $apiKey = getenv('MAILJET_API_KEY');
        $apiSecret = getenv('MAILJET_API_SECRET');

        $sender = new Mailjet(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
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

        $result = (array) \json_decode($sender->send($message));

        $this->assertEquals('success', $result['Messages'][0]->Status);
    }
}
