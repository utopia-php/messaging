<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\SES;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Tests\Adapter\Base;

/**
 * Live SES integration tests. These hit the real SES API and are skipped
 * unless SES credentials are provided via environment variables:
 *
 *   SES_ACCESS_KEY  AWS access key ID
 *   SES_SECRET_KEY  AWS secret access key
 *   SES_REGION      AWS region (e.g. us-east-1)
 *   SES_TEST_EMAIL  A verified SES identity used as both sender and recipient
 *   SES_SESSION_TOKEN  Optional session token for temporary credentials
 */
class SESTest extends Base
{
    private SES $sender;

    private string $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $accessKey = \getenv('SES_ACCESS_KEY') ?: '';
        $secretKey = \getenv('SES_SECRET_KEY') ?: '';
        $region = \getenv('SES_REGION') ?: '';
        $this->testEmail = \getenv('SES_TEST_EMAIL') ?: '';
        $sessionToken = \getenv('SES_SESSION_TOKEN') ?: null;

        if ($accessKey === '' || $secretKey === '' || $region === '' || $this->testEmail === '') {
            $this->markTestSkipped('SES credentials are not configured.');
        }

        $this->sender = new SES($accessKey, $secretKey, $region, $sessionToken);
    }

    public function testSendEmail(): void
    {
        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test Subject',
            content: 'Test Content',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithHtml(): void
    {
        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test HTML Subject',
            content: '<h1>Test HTML Content</h1><p>This is a test email.</p>',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
            html: true,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithReplyTo(): void
    {
        $message = new Email(
            to: [$this->testEmail],
            subject: 'Test Reply-To Subject',
            content: 'Test Content with Reply-To',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
            replyToName: 'Reply To Name',
            replyToEmail: $this->testEmail,
        );

        $response = $this->sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendMultipleEmails(): void
    {
        $message = new Email(
            to: [$this->testEmail, $this->testEmail],
            subject: 'Test Batch Subject',
            content: 'Test Batch Content',
            fromName: 'Test Sender',
            fromEmail: $this->testEmail,
        );

        $response = $this->sender->send($message);

        $this->assertSame(2, $response['deliveredTo'], \var_export($response, true));
        $this->assertSame('success', $response['results'][0]['status'], \var_export($response, true));
        $this->assertSame('success', $response['results'][1]['status'], \var_export($response, true));
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
}
