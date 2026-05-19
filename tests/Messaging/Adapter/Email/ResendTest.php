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

        $this->assertEquals(2, $response['deliveredTo'], \var_export($response, true));
        $this->assertEquals('', $response['results'][0]['error'], \var_export($response, true));
        $this->assertEquals('success', $response['results'][0]['status'], \var_export($response, true));
        $this->assertEquals('', $response['results'][1]['error'], \var_export($response, true));
        $this->assertEquals('success', $response['results'][1]['status'], \var_export($response, true));
    }

    public function testSendEmailWithFileAttachment(): void
    {
        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test File Attachment',
            content: 'Test Content with file attachment',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__.'/../../../assets/image.png',
                type: 'image/png'
            )],
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithStringAttachment(): void
    {
        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test String Attachment',
            content: 'Test Content with string attachment',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
            attachments: [new Attachment(
                name: 'test.txt',
                path: '',
                type: 'text/plain',
                content: 'Hello, this is a test attachment.',
            )],
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithAttachmentExceedingMaxSize(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Total attachment size exceeds');

        $largeContent = \str_repeat('x', 40 * 1024 * 1024 + 1);

        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test Oversized Attachment',
            content: 'Test Content',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
            attachments: [new Attachment(
                name: 'large.bin',
                path: '',
                type: 'application/octet-stream',
                content: $largeContent,
            )],
        );

        $this->sender->send($message);
    }
}
