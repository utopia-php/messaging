<?php

declare(strict_types=1);

namespace Utopia\Tests\Adapter\Email;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Adapter\Email\SMTP;
use Utopia\Messaging\Messages\Email;
use Utopia\SMTP\Exception\ConnectionException;
use Utopia\SMTP\Transport\Transport;

/**
 * A kept-alive session that the server closed while it sat idle. No network:
 * each session is a scripted transport, and what matters is whether the
 * adapter notices before it hands the server a message.
 */
final class SMTPKeepAliveTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function session(): array
    {
        return ['220 relay ready', '250 relay'];
    }

    /**
     * @return list<string>
     */
    private function accepted(): array
    {
        return ['250 OK', '250 OK', '354 Go ahead', '250 OK queued as A1'];
    }

    private function message(string $subject): Email
    {
        return new Email(
            to: ['tester@localhost.test'],
            subject: $subject,
            content: 'Body',
            fromName: 'Sender',
            fromEmail: 'sender@localhost.test',
        );
    }

    public function testAHealthySessionIsReused(): void
    {
        $adapter = new SMTPScripted(
            [new ScriptedTransport([...$this->session(), ...$this->accepted(), '250 OK', ...$this->accepted()])],
            host: 'relay.test',
            keepAlive: true,
        );

        $this->assertSame(1, $adapter->send($this->message('First'))['deliveredTo']);
        $this->assertSame(1, $adapter->send($this->message('Second'))['deliveredTo']);

        $this->assertCount(1, $adapter->transports);
        $this->assertSame(1, substr_count($adapter->transports[0]->written, 'NOOP'));
        $this->assertSame(2, substr_count($adapter->transports[0]->written, 'MAIL FROM:'));
    }

    public function testASessionTheServerClosedWithAGoodbyeIsReplaced(): void
    {
        $adapter = new SMTPScripted(
            [
                new ScriptedTransport([...$this->session(), ...$this->accepted(), '421 Timeout - closing connection']),
                new ScriptedTransport([...$this->session(), ...$this->accepted()]),
            ],
            host: 'relay.test',
            keepAlive: true,
        );

        $this->assertSame(1, $adapter->send($this->message('First'))['deliveredTo']);

        $response = $adapter->send($this->message('Second'));

        $this->assertSame(1, $response['deliveredTo'], var_export($response, true));
        $this->assertCount(2, $adapter->transports);
        $this->assertStringNotContainsString('Second', $adapter->transports[0]->written);
        $this->assertStringContainsString('Subject: Second', $adapter->transports[1]->written);
    }

    public function testASessionTheServerClosedSilentlyIsReplaced(): void
    {
        $adapter = new SMTPScripted(
            [
                new ScriptedTransport([...$this->session(), ...$this->accepted()]),
                new ScriptedTransport([...$this->session(), ...$this->accepted()]),
            ],
            host: 'relay.test',
            keepAlive: true,
        );

        $this->assertSame(1, $adapter->send($this->message('First'))['deliveredTo']);

        $response = $adapter->send($this->message('Second'));

        $this->assertSame(1, $response['deliveredTo'], var_export($response, true));
        $this->assertCount(2, $adapter->transports);
        $this->assertTrue($adapter->transports[0]->closed);
        $this->assertStringContainsString('Subject: Second', $adapter->transports[1]->written);
    }

    public function testAGoodbyeDuringASendDropsTheSessionForTheNextOne(): void
    {
        $adapter = new SMTPScripted(
            [
                new ScriptedTransport([...$this->session(), '421 Timeout - closing connection']),
                new ScriptedTransport([...$this->session(), ...$this->accepted()]),
            ],
            host: 'relay.test',
            keepAlive: true,
        );

        $first = $adapter->send($this->message('First'));

        $this->assertSame(0, $first['deliveredTo']);
        $this->assertSame('421 Timeout - closing connection', $first['results'][0]['error']);
        $this->assertTrue($adapter->transports[0]->closed);

        $second = $adapter->send($this->message('Second'));

        $this->assertSame(1, $second['deliveredTo'], var_export($second, true));
        $this->assertCount(2, $adapter->transports);
    }

    public function testWithoutKeepAliveEverySendIsItsOwnSession(): void
    {
        $adapter = new SMTPScripted(
            [
                new ScriptedTransport([...$this->session(), ...$this->accepted(), '221 Bye']),
                new ScriptedTransport([...$this->session(), ...$this->accepted(), '221 Bye']),
            ],
            host: 'relay.test',
        );

        $this->assertSame(1, $adapter->send($this->message('First'))['deliveredTo']);
        $this->assertSame(1, $adapter->send($this->message('Second'))['deliveredTo']);

        $this->assertCount(2, $adapter->transports);
        $this->assertStringNotContainsString('NOOP', $adapter->transports[0]->written);
        $this->assertStringNotContainsString('NOOP', $adapter->transports[1]->written);
    }
}

class SMTPScripted extends SMTP
{
    /**
     * @var list<ScriptedTransport>
     */
    public array $transports = [];

    /**
     * @param  list<ScriptedTransport>  $sessions  Handed out one per connection, in order.
     */
    public function __construct(
        private array $sessions,
        string $host,
        bool $keepAlive = false,
    ) {
        parent::__construct(host: $host, keepAlive: $keepAlive);
    }

    #[\Override]
    protected function transport(string $host, int $port): Transport
    {
        $transport = array_shift($this->sessions) ?? throw new \LogicException('The script has no session left to connect');

        $this->transports[] = $transport;

        return $transport;
    }
}

/**
 * A server whose replies are written down in advance. Once they run out the
 * connection reads as closed, which is what an idle timeout looks like.
 */
class ScriptedTransport implements Transport
{
    public string $written = '';

    public bool $closed = false;

    private string $pending = '';

    /**
     * @param  list<string>  $replies
     */
    public function __construct(array $replies)
    {
        foreach ($replies as $reply) {
            $this->pending .= $reply . "\r\n";
        }
    }

    public function connect(float $timeout, bool $tls): void {}

    public function read(int $length, float $timeout): string
    {
        if ($this->pending === '') {
            throw new ConnectionException('The server closed the connection');
        }

        $end = strpos($this->pending, "\r\n");
        $take = min($length, $end === false ? \strlen($this->pending) : $end + 2);
        $chunk = substr($this->pending, 0, $take);
        $this->pending = substr($this->pending, $take);

        return $chunk;
    }

    public function write(string $data, float $timeout): void
    {
        $this->written .= $data;
    }

    public function startTls(float $timeout): void {}

    public function isTls(): bool
    {
        return false;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
