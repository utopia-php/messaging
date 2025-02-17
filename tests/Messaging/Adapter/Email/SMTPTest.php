<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\SMTP;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Tests\Adapter\Base;

class SMTPTest extends Base
{
    public function testSendEmail(): void
    {
        $sender = new SMTP(
            host: 'maildev',
            port: 1025,
        );

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromName = 'Test Sender';
        $fromEmail = 'sender@localhost.test';

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: $fromName,
            fromEmail: $fromEmail,
        );

        $response = $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertResponse($response);
        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($fromEmail, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }

    public function testSendEmailWithAttachment(): void
    {
        $sender = new SMTP(
            host: 'maildev',
            port: 1025,
        );

        $to = 'tester@localhost.test';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromName = 'Test Sender';
        $fromEmail = 'sender@localhost.test';


        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: $fromName,
            fromEmail: $fromEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__ . '/../../../assets/image.png',
                type: 'image/png'
            )],
        );

        $response = $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertResponse($response);
        $this->assertEquals($to, $lastEmail['to'][0]['address']);
        $this->assertEquals($fromEmail, $lastEmail['from'][0]['address']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
    }

    public function testSendEmailOnlyBCC(): void
    {
        $defaultRecipient = \getenv('SMTP_DEFAULT_RECIPIENT') ?: 'tester@localhost.test';
        $sender = new SMTP(
            host: 'maildev',
            port: 1025,
            defaultRecipient: $defaultRecipient,
        );

        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromName = 'Test Sender';
        $fromEmail = 'sender@localhost.test';
        $bcc = [
            [
                'email' => 'tester@localhost.test',
                'name' => 'Test Recipient',
            ],
        ];

        $message = new Email(
            to: [],
            subject: $subject,
            content: $content,
            fromName: $fromName,
            fromEmail: $fromEmail,
            bcc: $bcc,
        );

        $response = $sender->send($message);

        $this->assertEquals(0, $response['deliveredTo'], \var_export($response, true));
        $this->assertEquals('email', $response['type'], \var_export($response, true));
        $this->assertEquals('', $response['results'][0]['error'], \var_export($response, true));
    }
}
