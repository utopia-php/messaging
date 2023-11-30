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
        $from = 'sender@localhost.test';

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        $response = \json_decode($sender->send($message), true);

        $lastEmail = $this->getLastEmail();

        $this->assertResponse($response);
        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($from, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }
}
