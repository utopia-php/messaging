<?php

namespace Tests\E2E;

use Utopia\Messaging\Email\EmailMessage;
use Utopia\Messaging\Email\Mock;

class EmailTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $mail = new Mock();

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@localhost.test';

        $message = new EmailMessage(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        $mail->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($from, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }
}
