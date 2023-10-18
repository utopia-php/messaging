<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Mandrill;
use Utopia\Messaging\Messages\Email;

class MandrillTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('MANDRILL_API_KEY');
        $sender = new Mandrill($key);

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

        $this->assertEquals($response, '');
    }
}
