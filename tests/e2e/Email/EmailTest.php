<?php

namespace Tests\E2E\Email;

use Tests\E2E\Base;
use Utopia\Messaging\Adapters\Email\Mock;
use Utopia\Messaging\Messages\Email;

class EmailTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail(): void
    {
        $sender = new Mock();

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@localhost.test';

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($from, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }
}
