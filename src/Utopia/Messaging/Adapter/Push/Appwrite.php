<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Helpers\JWT;
use Utopia\Messaging\Helpers\MQTT;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Priority;
use Utopia\Messaging\Response;

/**
 * Appwrite Push (MQTT 5) adapter.
 *
 * Publishes notifications to Appwrite's MQTT broker, which then fans them out
 * to subscribed devices over a single persistent connection per device.
 *
 * Connects to the broker over TLS as a server-scoped client, authenticates
 * with a short-lived HMAC-signed JWT, then PUBLISHes one message per device
 * token onto the device-specific topic.
 */
class Appwrite extends PushAdapter
{
    protected const NAME = 'Appwrite';
    protected const TOPIC_PREFIX = 'appwrite/push';
    protected const SERVER_CLIENT_PREFIX = 'appwrite-server';
    protected const JWT_ALGORITHM = 'HS256';
    protected const JWT_SCOPE = 'server';
    protected const JWT_TTL = 60;
    protected const CONNECT_TIMEOUT = 5;
    protected const READ_TIMEOUT = 10;
    protected const KEEP_ALIVE = 30;
    protected const DEFAULT_MESSAGE_EXPIRY = 86400;

    private int $packetId = 0;

    /**
     * Persistent read buffer carrying over bytes the decoder didn't yet consume.
     * MQTT packets can be coalesced into a single TCP read and we'd otherwise
     * lose them between calls to readPacket().
     */
    private string $readBuffer = '';

    /**
     * Max number of unacknowledged PUBLISHes in flight at any time. MQTT 5's
     * Receive Maximum default is 65535 but most real brokers advertise a smaller
     * value in CONNACK; we honor whichever is smaller after handshake.
     */
    private int $receiveMaximum = 256;

    public function __construct(
        private string $endpoint,
        private string $signingKey,
        private bool $tls = true,
        private int $messageExpiry = self::DEFAULT_MESSAGE_EXPIRY,
        private string $serverId = '',
    ) {
        if ($this->serverId === '') {
            $this->serverId = self::SERVER_CLIENT_PREFIX . '-' . \bin2hex(\random_bytes(6));
        }
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(PushMessage $message): array
    {
        $payload = $this->buildPayload($message);
        $expiry = $this->resolveExpiry($message);

        $response = new Response($this->getType());

        $socket = $this->connect();

        try {
            $this->handshake($socket);
            $this->pipelinedPublish($socket, $message->getTo(), $payload, $expiry, $response);

            try {
                $this->write($socket, MQTT::encodeDisconnect());
            } catch (\Throwable) {
                // Best effort; some brokers may have already closed the socket.
            }
        } finally {
            $this->close($socket);
        }

        return $response->toArray();
    }

    /**
     * Pipelined PUBLISH/PUBACK loop.
     *
     * Sends up to `receiveMaximum` PUBLISH packets without waiting for an
     * acknowledgment, then drains PUBACKs as they arrive, matching each by
     * packet id. Refills the in-flight window after each ack until every
     * device has been sent. This keeps throughput proportional to socket
     * bandwidth rather than to network RTT — important when fanning out to
     * thousands of devices per request.
     *
     * @param resource $socket
     * @param array<string> $tokens
     */
    private function pipelinedPublish($socket, array $tokens, string $payload, int $expiry, Response $response): void
    {
        $inflight = [];
        $cursor = 0;
        $total = \count($tokens);

        while ($cursor < $total || !empty($inflight)) {
            while ($cursor < $total && \count($inflight) < $this->receiveMaximum) {
                $token = $tokens[$cursor++];
                $packetId = $this->nextPacketId();

                try {
                    $packet = MQTT::encodePublish(
                        topic: $this->topicForToken($token),
                        payload: $payload,
                        qos: 1,
                        retain: false,
                        dup: false,
                        packetId: $packetId,
                        properties: [
                            'messageExpiryInterval' => $expiry,
                            'contentType' => 'application/json',
                        ],
                    );
                    $this->write($socket, $packet);
                    $inflight[$packetId] = $token;
                } catch (\Throwable $error) {
                    $response->addResult($token, $error->getMessage());
                }
            }

            if (empty($inflight)) {
                continue;
            }

            try {
                $ack = $this->readPacket($socket);
            } catch (\Throwable $error) {
                foreach ($inflight as $token) {
                    $response->addResult($token, $error->getMessage());
                }
                return;
            }

            if ($ack['type'] !== MQTT::PACKET_PUBACK) {
                continue;
            }

            $parsed = MQTT::parsePuback($ack['payload']);
            $token = $inflight[$parsed['packetId']] ?? null;
            if ($token === null) {
                continue;
            }
            unset($inflight[$parsed['packetId']]);

            if ($parsed['reasonCode'] !== MQTT::REASON_SUCCESS) {
                $response->addResult($token, $this->errorForReasonCode($parsed['reasonCode']));
                continue;
            }

            $response->incrementDeliveredTo();
            $response->addResult($token);
        }
    }

    /**
     * Build a single payload that the device runtime can render. Mirrors the
     * shape exposed to FCM/APNS so SDK consumers see a consistent envelope.
     *
     * @return string JSON-encoded payload
     */
    private function buildPayload(PushMessage $message): string
    {
        $envelope = [];

        if ($message->getTitle() !== null) {
            $envelope['notification']['title'] = $message->getTitle();
        }
        if ($message->getBody() !== null) {
            $envelope['notification']['body'] = $message->getBody();
        }
        if ($message->getImage() !== null) {
            $envelope['notification']['image'] = $message->getImage();
        }
        if ($message->getIcon() !== null) {
            $envelope['notification']['icon'] = $message->getIcon();
        }
        if ($message->getColor() !== null) {
            $envelope['notification']['color'] = $message->getColor();
        }
        if ($message->getSound() !== null) {
            $envelope['notification']['sound'] = $message->getSound();
        }
        if ($message->getTag() !== null) {
            $envelope['notification']['tag'] = $message->getTag();
        }
        if ($message->getBadge() !== null) {
            $envelope['notification']['badge'] = $message->getBadge();
        }
        if ($message->getAction() !== null) {
            $envelope['notification']['action'] = $message->getAction();
        }
        if ($message->getContentAvailable() !== null) {
            $envelope['notification']['contentAvailable'] = (bool)$message->getContentAvailable();
        }
        if ($message->getCritical() !== null) {
            $envelope['notification']['critical'] = (bool)$message->getCritical();
        }
        if ($message->getData() !== null) {
            $envelope['data'] = $message->getData();
        }
        if ($message->getPriority() !== null) {
            $envelope['priority'] = match ($message->getPriority()) {
                Priority::HIGH => 'high',
                Priority::NORMAL => 'normal',
            };
        }

        $json = \json_encode($envelope, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode push payload: ' . \json_last_error_msg());
        }

        return $json;
    }

    private function resolveExpiry(PushMessage $message): int
    {
        if (\method_exists($message, 'getMessageExpiry')) {
            $expiry = $message->getMessageExpiry();
            if (\is_int($expiry) && $expiry > 0) {
                return $expiry;
            }
        }

        return $this->messageExpiry;
    }

    private function topicForToken(string $token): string
    {
        return self::TOPIC_PREFIX . '/' . $token;
    }

    private function nextPacketId(): int
    {
        $this->packetId = ($this->packetId + 1) & 0xFFFF;
        if ($this->packetId === 0) {
            $this->packetId = 1;
        }

        return $this->packetId;
    }

    /**
     * @return resource
     */
    private function connect()
    {
        $url = $this->resolveEndpoint();
        $context = \stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
            ],
        ]);

        $socket = @\stream_socket_client(
            $url,
            $errno,
            $errstr,
            self::CONNECT_TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if (!$socket) {
            throw new \RuntimeException("Unable to connect to Appwrite Push broker at {$url}: {$errstr} (errno {$errno})");
        }

        \stream_set_timeout($socket, self::READ_TIMEOUT);

        return $socket;
    }

    private function resolveEndpoint(): string
    {
        $endpoint = \rtrim($this->endpoint);
        if ($endpoint === '') {
            throw new \RuntimeException('Appwrite Push endpoint is not configured');
        }

        $scheme = $this->tls ? 'tls' : 'tcp';

        if (\str_contains($endpoint, '://')) {
            $parts = \parse_url($endpoint);
            $host = $parts['host'] ?? '';
            $port = $parts['port'] ?? ($this->tls ? 8883 : 1883);

            return "{$scheme}://{$host}:{$port}";
        }

        if (\str_contains($endpoint, ':')) {
            return "{$scheme}://{$endpoint}";
        }

        $port = $this->tls ? 8883 : 1883;

        return "{$scheme}://{$endpoint}:{$port}";
    }

    /**
     * @param resource $socket
     */
    private function handshake($socket): void
    {
        $token = $this->issueServerJwt();
        $packet = MQTT::encodeConnect(
            clientId: $this->serverId,
            username: 'server',
            password: $token,
            keepAlive: self::KEEP_ALIVE,
            cleanStart: true,
            properties: [
                'sessionExpiryInterval' => 0,
            ],
        );

        $this->write($socket, $packet);

        $response = $this->readPacket($socket);
        if ($response['type'] !== MQTT::PACKET_CONNACK) {
            throw new \RuntimeException('Broker did not respond with CONNACK');
        }

        $connack = MQTT::parseConnack($response['payload']);
        if ($connack['reasonCode'] !== MQTT::REASON_SUCCESS) {
            throw new \RuntimeException("Broker rejected CONNECT (reason {$connack['reasonCode']})");
        }

        $brokerLimit = (int)($connack['properties']['receiveMaximum'] ?? 0);
        if ($brokerLimit > 0) {
            $this->receiveMaximum = \min($this->receiveMaximum, $brokerLimit);
        }
    }

    private function issueServerJwt(): string
    {
        $now = \time();
        $claims = [
            'iss' => 'appwrite',
            'sub' => $this->serverId,
            'iat' => $now,
            'exp' => $now + self::JWT_TTL,
            'scope' => self::JWT_SCOPE,
        ];

        return JWT::encode($claims, $this->signingKey, self::JWT_ALGORITHM);
    }

    /**
     * @param resource $socket
     * @return array{type: int, flags: int, payload: string}
     */
    private function readPacket($socket): array
    {
        while (true) {
            $packet = MQTT::decodePacket($this->readBuffer);
            if ($packet !== null) {
                return $packet;
            }

            $chunk = @\fread($socket, 4096);
            if ($chunk === false || $chunk === '') {
                if (\feof($socket)) {
                    throw new \RuntimeException('Broker closed the connection');
                }

                $info = \stream_get_meta_data($socket);
                if (!empty($info['timed_out'])) {
                    throw new \RuntimeException('Broker read timeout');
                }

                continue;
            }

            $this->readBuffer .= $chunk;
        }
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $bytes): void
    {
        $length = \strlen($bytes);
        $written = 0;

        while ($written < $length) {
            $result = @\fwrite($socket, \substr($bytes, $written));
            if ($result === false || $result === 0) {
                throw new \RuntimeException('Failed to write to broker socket');
            }
            $written += $result;
        }
    }

    /**
     * @param resource $socket
     */
    private function close($socket): void
    {
        if (\is_resource($socket)) {
            @\fclose($socket);
        }
    }

    private function errorForReasonCode(int $code): string
    {
        return match ($code) {
            0x10 => $this->getExpiredErrorMessage(), // No matching subscribers
            0x90 => 'Topic name invalid',
            0x97 => 'Quota exceeded',
            0x99 => 'Payload format invalid',
            0x87 => 'Not authorized',
            default => "Broker returned reason code 0x" . \dechex($code),
        };
    }
}
