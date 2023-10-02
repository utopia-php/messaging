<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mailtrap;
use Utopia\Messaging\Messages\Email;

class MailtrapTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendPlainTextEmail()
    {
        $this->markTestSkipped('Mailtrap credentials not set.');

        $key = getenv('MAILTAP_API_KEY');
        $sender = new Mailtrap($key);

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
        
        $this->assertArrayHasKey('messageId', json_decode($response, true));
    }
}