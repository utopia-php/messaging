<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Mailgun;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Tests\Adapter\Base;

class MailgunTest extends Base
{
    public function testSendEmail(): void
    {
        $key = \getenv('MAILGUN_API_KEY');
        $domain = \getenv('MAILGUN_DOMAIN');

        $sender = new Mailgun(
            apiKey: $key,
            domain: $domain,
            isEU: false
        );

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithAttachments(): void
    {
        $key = \getenv('MAILGUN_API_KEY');
        $domain = \getenv('MAILGUN_DOMAIN');

        $sender = new Mailgun(
            apiKey: $key,
            domain: $domain,
            isEU: false
        );

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__ . '/../../../assets/image.png',
                type: 'image/png'
            ),],
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
