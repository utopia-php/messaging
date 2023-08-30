<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;

class SendgridTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

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

        $sender->send($message);

        $this->assertEquals(true, true);
    }
}
