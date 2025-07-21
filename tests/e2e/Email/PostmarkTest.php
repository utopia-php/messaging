<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Postmark;
use Utopia\Messaging\Messages\Email;

class PostmarkTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendPlainTextEmail()
    {
        $key = getenv('POSTMARK_API_KEY');
        $sender = new Postmark($key);

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

        $this->assertEquals($to, $result['To']);
        $this->assertEquals('OK', $result['Message']);
    }
}
