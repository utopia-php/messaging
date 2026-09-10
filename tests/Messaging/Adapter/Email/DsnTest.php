<?php

namespace Utopia\Tests\Adapter\Email;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Adapter\Email\Mailgun;
use Utopia\Messaging\Adapter\Email\Resend;
use Utopia\Messaging\Adapter\Email\Sendgrid;
use Utopia\Messaging\Adapter\Email\SMTP;

class DsnTest extends TestCase
{
    public function test_creates_smtp_adapter_from_dsn(): void
    {
        $adapter = EmailAdapter::fromDsn(
            'smtp://user:pass@mail.example.com:587?secure=tls&autotls=1&xmailer=Appwrite&timeout=60&keepalive=1&timelimit=15'
        );

        $this->assertInstanceOf(SMTP::class, $adapter);
        $this->assertSame('mail.example.com', $this->readProperty($adapter, 'host'));
        $this->assertSame(587, $this->readProperty($adapter, 'port'));
        $this->assertSame('user', $this->readProperty($adapter, 'username'));
        $this->assertSame('pass', $this->readProperty($adapter, 'password'));
        $this->assertSame('tls', $this->readProperty($adapter, 'smtpSecure'));
        $this->assertTrue($this->readProperty($adapter, 'smtpAutoTLS'));
        $this->assertSame('Appwrite', $this->readProperty($adapter, 'xMailer'));
        $this->assertSame(60, $this->readProperty($adapter, 'timeout'));
        $this->assertTrue($this->readProperty($adapter, 'keepAlive'));
        $this->assertSame(15, $this->readProperty($adapter, 'timelimit'));
    }

    public function test_creates_smtps_adapter_with_implicit_ssl_defaults(): void
    {
        $adapter = EmailAdapter::fromDsn('smtps://user:pass@mail.example.com');

        $this->assertInstanceOf(SMTP::class, $adapter);
        $this->assertSame(465, $this->readProperty($adapter, 'port'));
        $this->assertSame('ssl', $this->readProperty($adapter, 'smtpSecure'));
    }

    public function test_creates_resend_adapter_from_dsn(): void
    {
        $adapter = EmailAdapter::fromDsn('resend://re_test_key@default');

        $this->assertInstanceOf(Resend::class, $adapter);
        $this->assertSame('re_test_key', $this->readProperty($adapter, 'apiKey'));
    }

    public function test_creates_sendgrid_adapter_from_dsn(): void
    {
        $adapter = EmailAdapter::fromDsn('sendgrid://sg_test_key@default');

        $this->assertInstanceOf(Sendgrid::class, $adapter);
        $this->assertSame('sg_test_key', $this->readProperty($adapter, 'apiKey'));
    }

    public function test_creates_mailgun_adapter_from_dsn(): void
    {
        $adapter = EmailAdapter::fromDsn('mailgun://mg_test_key@example.com?eu=1');

        $this->assertInstanceOf(Mailgun::class, $adapter);
        $this->assertSame('mg_test_key', $this->readProperty($adapter, 'apiKey'));
        $this->assertSame('example.com', $this->readProperty($adapter, 'domain'));
        $this->assertTrue($this->readProperty($adapter, 'isEU'));
    }

    public function test_rejects_unsupported_scheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported email DSN scheme "ses".');

        EmailAdapter::fromDsn('ses://key@default');
    }

    public function test_rejects_invalid_dsn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email DSN.');

        EmailAdapter::fromDsn('not a dsn');
    }

    public function test_rejects_malformed_smtp_dsn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email DSN.');

        EmailAdapter::fromDsn('smtp://');
    }

    public function test_rejects_missing_resend_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resend DSN must include an API key.');

        EmailAdapter::fromDsn('resend://@default');
    }

    public function test_rejects_invalid_boolean_query_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "autotls" option. Expected boolean-like value.');

        EmailAdapter::fromDsn('smtp://mail.example.com?autotls=maybe');
    }

    public function test_rejects_invalid_integer_query_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "timeout" option. Expected integer value.');

        EmailAdapter::fromDsn('smtp://mail.example.com?timeout=fast');
    }

    public function test_rejects_invalid_secure_query_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SMTP "secure" option. Expected "", "ssl", or "tls".');

        EmailAdapter::fromDsn('smtp://mail.example.com?secure=starttls');
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);

        return $property->getValue($object);
    }
}
