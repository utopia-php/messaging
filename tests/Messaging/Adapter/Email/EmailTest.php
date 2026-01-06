<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Mock;
use Utopia\Messaging\Messages\Email;
use Utopia\Tests\Adapter\Base;

class EmailTest extends Base
{
    public function testSendEmail(): void
    {
        $sender = new Mock();

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromName = 'Test Sender';
        $fromEmail = 'sender@localhost.test';
        $cc = [['email' => 'tester2@localhost.test']];
        $bcc = [['name' => 'Tester3', 'email' => 'tester3@localhost.test']];

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: $fromName,
            fromEmail: $fromEmail,
            cc: $cc,
            bcc: $bcc,
        );

        $response = $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertResponse($response);
        $this->assertSame($to, $lastEmail['to'][0]['address']);
        $this->assertSame($fromEmail, $lastEmail['from'][0]['address']);
        $this->assertSame($fromName, $lastEmail['from'][0]['name']);
        $this->assertSame($subject, $lastEmail['subject']);
        $this->assertSame($content, \trim($lastEmail['text']));
        $this->assertSame($cc[0]['email'], $lastEmail['cc'][0]['address']);
        $this->assertSame($bcc[0]['email'], $lastEmail['envelope']['to'][2]['address']);
    }
}
