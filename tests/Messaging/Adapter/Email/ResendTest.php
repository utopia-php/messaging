<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Resend;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Tests\Adapter\Base;

class ResendTest extends Base
{
    private Resend $sender;
    private string $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $key = \getenv('RESEND_API_KEY');
        $this->sender = new Resend($key);
        $this->testEmail = \getenv('RESEND_TEST_EMAIL');

        sleep(2);
    }

    public function testSendEmail(): void
    {
        $to = $this->testEmail;
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = $this->testEmail;
        $cc = [['email' => $this->testEmail]];
        $bcc = [['name' => 'Test BCC', 'email' => $this->testEmail]];

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            cc: $cc,
            bcc: $bcc,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithHtml(): void
    {
        $to = $this->testEmail;
        $subject = 'Test HTML Subject';
        $content = '<h1>Test HTML Content</h1><p>This is a test email with HTML content.</p>';
        $fromEmail = $this->testEmail;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            html: true,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithReplyTo(): void
    {
        $to = $this->testEmail;
        $subject = 'Test Reply-To Subject';
        $content = 'Test Content with Reply-To';
        $fromEmail = $this->testEmail;
        $replyToEmail = $this->testEmail;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            replyToName: 'Reply To Name',
            replyToEmail: $replyToEmail,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendMultipleEmails(): void
    {
        $to1 = $this->testEmail;
        $to2 = $this->testEmail;
        $subject = 'Test Batch Subject';
        $content = 'Test Batch Content';
        $fromEmail = $this->testEmail;

        $message = new Email(
            to: [$to1, $to2],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
        );

        $response = $this->sender->send($message);

        $this->assertSame(2, $response['deliveredTo'], \var_export($response, true));
        $this->assertSame('', $response['results'][0]['error'], \var_export($response, true));
        $this->assertSame('success', $response['results'][0]['status'], \var_export($response, true));
        $this->assertSame('', $response['results'][1]['error'], \var_export($response, true));
        $this->assertSame('success', $response['results'][1]['status'], \var_export($response, true));
    }

    public function testSendEmailWithAttachmentsThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Resend does not support attachments at this time');

        $to = $this->testEmail;
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = $this->testEmail;

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__.'/../../../assets/image.png',
                type: 'image/png'
            )],
        );

        $this->sender->send($message);
    }
}
