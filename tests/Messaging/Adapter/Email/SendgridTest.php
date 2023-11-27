<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;
use Utopia\Tests\Adapter\Base;

class SendgridTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail(): void
    {
        /*
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from,
        );

        $response = $sender->send($message);

        $this->assertEquals($response, '');
        */

        $this->markTestSkipped('Sendgrid: Authenticated user is not authorized to send mail');
    }
}
