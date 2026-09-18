<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Brevo;
use Utopia\Messaging\Messages\Email;

class BrevoTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendPlainTextEmail()
    {
        // $this->markTestSkipped('Brevo credentials not set.');

        $key = getenv('BREVO_API_KEY');
        $sender = new Brevo($key);

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