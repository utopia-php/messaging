<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;
use Utopia\Tests\Adapter\Base;

class SendgridTest extends Base
{
    public function testSendEmail(): void
    {
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Tester',
            fromEmail: $fromEmail,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithAttachment(): void
    {
        $key = \getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $image = \file_get_contents(__DIR__ . '/../../../assets/image.png');
        $image = \base64_encode($image);

        $attachment = new Email\Attachment(
            name: 'image.png',
            content: $image,
            type: 'image/png'
        );

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Tester',
            fromEmail: $fromEmail,
            attachments: [$attachment],
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
