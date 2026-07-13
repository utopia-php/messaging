<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Email as EmailMessage;

abstract class Email extends Adapter
{
    protected const TYPE = 'email';

    protected const MESSAGE_TYPE = EmailMessage::class;

    protected const MAX_ATTACHMENT_BYTES = 25 * 1024 * 1024; // 25MB

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getMessageType(): string
    {
        return static::MESSAGE_TYPE;
    }

    /**
     * Create an email adapter from a DSN string.
     *
     * Supported schemes: smtp, smtps, resend, sendgrid, mailgun.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromDsn(string $dsn): self
    {
        $parts = \parse_url($dsn);

        if ($parts === false || empty($parts['scheme'])) {
            throw new \InvalidArgumentException('Invalid email DSN.');
        }

        $scheme = \strtolower($parts['scheme']);
        $query = [];

        if (isset($parts['query'])) {
            \parse_str($parts['query'], $query);
        }

        return match ($scheme) {
            'smtp', 'smtps' => self::createSmtpAdapter($parts, $query, $scheme),
            'resend' => self::createApiKeyAdapter($parts, Email\Resend::class, 'Resend'),
            'sendgrid' => self::createApiKeyAdapter($parts, Email\Sendgrid::class, 'Sendgrid'),
            'mailgun' => self::createMailgunAdapter($parts, $query),
            default => throw new \InvalidArgumentException('Unsupported email DSN scheme "'.$scheme.'".'),
        };
    }

    /**
     * Process an email message.
     *
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception
     */
    abstract protected function process(EmailMessage $message): array;

    /**
     * @param  array<string, int|string>  $parts
     * @param  array<string, mixed>  $query
     */
    private static function createSmtpAdapter(array $parts, array $query, string $scheme): self
    {
        $host = self::decodeUrlComponent($parts['host'] ?? null);

        if ($host === null || $host === '') {
            throw new \InvalidArgumentException('SMTP DSN must include a host.');
        }

        $port = self::parseIntOption(
            value: $query['port'] ?? ($parts['port'] ?? ($scheme === 'smtps' ? 465 : 25)),
            option: 'port'
        );

        $smtpSecure = self::parseSmtpSecureOption($query['secure'] ?? ($scheme === 'smtps' ? 'ssl' : ''));
        $smtpAutoTLS = self::parseBoolOption($query['autotls'] ?? false, 'autotls');
        $timeout = self::parseIntOption($query['timeout'] ?? 30, 'timeout');
        $keepAlive = self::parseBoolOption($query['keepalive'] ?? false, 'keepalive');
        $timelimit = self::parseIntOption($query['timelimit'] ?? 30, 'timelimit');
        $xMailer = self::parseStringOption($query['xmailer'] ?? '');

        return new Email\SMTP(
            host: $host,
            port: $port,
            username: self::decodeUrlComponent($parts['user'] ?? null) ?? '',
            password: self::decodeUrlComponent($parts['pass'] ?? null) ?? '',
            smtpSecure: $smtpSecure,
            smtpAutoTLS: $smtpAutoTLS,
            xMailer: $xMailer,
            timeout: $timeout,
            keepAlive: $keepAlive,
            timelimit: $timelimit,
        );
    }

    /**
     * @param  array<string, int|string>  $parts
     * @param  class-string<self>  $adapterClass
     */
    private static function createApiKeyAdapter(array $parts, string $adapterClass, string $adapterName): self
    {
        $apiKey = self::decodeUrlComponent($parts['user'] ?? null)
            ?? self::decodeUrlComponent($parts['pass'] ?? null);

        if ($apiKey === null || $apiKey === '') {
            throw new \InvalidArgumentException($adapterName.' DSN must include an API key.');
        }

        return new $adapterClass($apiKey);
    }

    /**
     * @param  array<string, int|string>  $parts
     * @param  array<string, mixed>  $query
     */
    private static function createMailgunAdapter(array $parts, array $query): self
    {
        $apiKey = self::decodeUrlComponent($parts['user'] ?? null)
            ?? self::decodeUrlComponent($parts['pass'] ?? null);

        if ($apiKey === null || $apiKey === '') {
            throw new \InvalidArgumentException('Mailgun DSN must include an API key.');
        }

        $domain = self::decodeUrlComponent($parts['host'] ?? null);

        if ($domain === null || $domain === '') {
            throw new \InvalidArgumentException('Mailgun DSN must include a domain.');
        }

        return new Email\Mailgun(
            apiKey: $apiKey,
            domain: $domain,
            isEU: self::parseBoolOption($query['eu'] ?? false, 'eu'),
        );
    }

    private static function decodeUrlComponent(mixed $value): ?string
    {
        if (! \is_string($value) || $value === '') {
            return null;
        }

        return \rawurldecode($value);
    }

    private static function parseStringOption(mixed $value): string
    {
        if (! \is_string($value)) {
            throw new \InvalidArgumentException('Expected string query parameter value.');
        }

        return $value;
    }

    private static function parseSmtpSecureOption(mixed $value): string
    {
        if (! \is_string($value)) {
            throw new \InvalidArgumentException('Invalid SMTP "secure" option. Expected "", "ssl", or "tls".');
        }

        $value = \strtolower($value);

        if (! \in_array($value, ['', 'ssl', 'tls'], true)) {
            throw new \InvalidArgumentException('Invalid SMTP "secure" option. Expected "", "ssl", or "tls".');
        }

        return $value;
    }

    private static function parseBoolOption(mixed $value, string $option): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (! \is_string($value) && ! \is_int($value)) {
            throw new \InvalidArgumentException('Invalid "'.$option.'" option. Expected boolean-like value.');
        }

        $normalized = \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($normalized === null) {
            throw new \InvalidArgumentException('Invalid "'.$option.'" option. Expected boolean-like value.');
        }

        return $normalized;
    }

    private static function parseIntOption(mixed $value, string $option): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (! \is_string($value) || $value === '' || ! \ctype_digit($value)) {
            throw new \InvalidArgumentException('Invalid "'.$option.'" option. Expected integer value.');
        }

        return (int) $value;
    }
}
