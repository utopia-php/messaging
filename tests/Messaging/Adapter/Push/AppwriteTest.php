<?php

namespace Utopia\Tests\Adapter\Push;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Adapter\Push\Appwrite;
use Utopia\Messaging\Messages\Push;

class AppwriteTest extends TestCase
{
    private const SIGNING_KEY = 'unit-test-signing-key';

    public function testSendPublishesToDeviceTopicAndCountsAck(): void
    {
        $broker = $this->startBroker(['device-token-1', 'device-token-2']);

        try {
            $adapter = new Appwrite(
                endpoint: '127.0.0.1:' . $broker['port'],
                signingKey: self::SIGNING_KEY,
                tls: false,
            );

            $message = new Push(
                to: ['device-token-1', 'device-token-2'],
                title: 'Hi',
                body: 'Hello',
                data: ['k' => 'v'],
            );

            $response = $adapter->send($message);

            $this->assertSame(2, $response['deliveredTo']);
            $this->assertSame('push', $response['type']);
            $this->assertCount(2, $response['results']);

            foreach ($response['results'] as $result) {
                $this->assertSame('success', $result['status']);
                $this->assertSame('', $result['error']);
            }

            $captured = $this->stopBroker($broker);

            $this->assertCount(2, $captured['publishes']);
            $this->assertSame('appwrite/push/device-token-1', $captured['publishes'][0]['topic']);
            $this->assertSame('appwrite/push/device-token-2', $captured['publishes'][1]['topic']);

            $decoded = \json_decode($captured['publishes'][0]['payload'], true);
            $this->assertSame('Hi', $decoded['notification']['title']);
            $this->assertSame('Hello', $decoded['notification']['body']);
            $this->assertSame(['k' => 'v'], $decoded['data']);

            $this->assertSame('server', $captured['connect']['username']);
            $this->assertNotEmpty($captured['connect']['password']);
            $this->assertStringStartsWith('appwrite-server-', $captured['connect']['clientId']);
        } finally {
            $this->stopBroker($broker, suppress: true);
        }
    }

    public function testPipelinesPublishesToManyDevices(): void
    {
        $tokens = [];
        for ($i = 0; $i < 64; $i++) {
            $tokens[] = "device-{$i}";
        }

        $broker = $this->startBroker($tokens);

        try {
            $adapter = new Appwrite(
                endpoint: '127.0.0.1:' . $broker['port'],
                signingKey: self::SIGNING_KEY,
                tls: false,
            );

            $message = new Push(
                to: $tokens,
                title: 'Burst',
                body: 'Pipeline test',
            );

            $response = $adapter->send($message);

            $this->assertSame(\count($tokens), $response['deliveredTo']);
            $this->assertCount(\count($tokens), $response['results']);

            $captured = $this->stopBroker($broker);
            $this->assertCount(\count($tokens), $captured['publishes']);

            $seenTopics = \array_map(fn ($p) => $p['topic'], $captured['publishes']);
            \sort($seenTopics);
            $expectedTopics = \array_map(fn ($t) => 'appwrite/push/' . $t, $tokens);
            \sort($expectedTopics);
            $this->assertSame($expectedTopics, $seenTopics);
        } finally {
            $this->stopBroker($broker, suppress: true);
        }
    }

    public function testReportsExpiredTokenOnBrokerReasonCode(): void
    {
        $broker = $this->startBroker(['live-token'], rejectTokens: ['stale-token']);

        try {
            $adapter = new Appwrite(
                endpoint: '127.0.0.1:' . $broker['port'],
                signingKey: self::SIGNING_KEY,
                tls: false,
            );

            $message = new Push(
                to: ['live-token', 'stale-token'],
                title: 'Hi',
                body: 'Hello',
            );

            $response = $adapter->send($message);

            $this->assertSame(1, $response['deliveredTo']);
            $this->assertSame('success', $response['results'][0]['status']);
            $this->assertSame('live-token', $response['results'][0]['recipient']);
            $this->assertSame('failure', $response['results'][1]['status']);
            $this->assertSame('stale-token', $response['results'][1]['recipient']);
            $this->assertSame('Expired device token', $response['results'][1]['error']);
        } finally {
            $this->stopBroker($broker, suppress: true);
        }
    }

    /**
     * @param array<string> $expectTokens
     * @param array<string> $rejectTokens
     * @return array{port: int, process: resource, captured: string}
     */
    private function startBroker(array $expectTokens, array $rejectTokens = []): array
    {
        $port = $this->pickFreePort();
        $capturePath = \sys_get_temp_dir() . '/appwrite-push-broker-' . \uniqid() . '.json';
        $stateFile = \sys_get_temp_dir() . '/appwrite-push-broker-state-' . \uniqid() . '.json';

        \file_put_contents($stateFile, \json_encode([
            'expect' => $expectTokens,
            'reject' => $rejectTokens,
        ]));

        $brokerScript = __DIR__ . '/FakeBroker.php';

        $process = \proc_open(
            [PHP_BINARY, $brokerScript, (string)$port, $capturePath, $stateFile],
            [
                0 => ['pipe', 'r'],
                1 => ['file', '/dev/null', 'a'],
                2 => ['file', '/dev/null', 'a'],
            ],
            $pipes,
        );

        if (!\is_resource($process)) {
            $this->fail('Could not start fake broker process');
        }

        $deadline = \microtime(true) + 3;
        while (\microtime(true) < $deadline) {
            $probe = @\fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if (\is_resource($probe)) {
                \fclose($probe);
                return [
                    'port' => $port,
                    'process' => $process,
                    'captured' => $capturePath,
                ];
            }
            \usleep(50000);
        }

        \proc_terminate($process);
        \proc_close($process);
        $this->fail("Broker on port {$port} did not come up in time");
    }

    /**
     * @param array{port: int, process: resource, captured: string} $broker
     * @return array{publishes: array<int, array{topic: string, payload: string}>, connect: array<string, string>}
     */
    private function stopBroker(array $broker, bool $suppress = false): array
    {
        if (\is_resource($broker['process'])) {
            \proc_terminate($broker['process'], SIGTERM);
            $deadline = \microtime(true) + 1;
            while (\microtime(true) < $deadline) {
                $status = \proc_get_status($broker['process']);
                if (!$status['running']) {
                    break;
                }
                \usleep(25000);
            }
            \proc_close($broker['process']);
        }

        if (!\file_exists($broker['captured'])) {
            if ($suppress) {
                return ['publishes' => [], 'connect' => []];
            }
            $this->fail("Broker capture file missing: {$broker['captured']}");
        }

        $captured = \json_decode(\file_get_contents($broker['captured']), true);
        @\unlink($broker['captured']);

        return $captured ?: ['publishes' => [], 'connect' => []];
    }

    private function pickFreePort(): int
    {
        $sock = \stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$sock) {
            $this->fail("Could not bind ephemeral port: {$errstr}");
        }
        $name = \stream_socket_get_name($sock, false);
        \fclose($sock);

        return (int)\explode(':', $name)[1];
    }
}
