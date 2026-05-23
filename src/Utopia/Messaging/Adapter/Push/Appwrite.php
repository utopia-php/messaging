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

            foreach ($message->getTo() as $token) {
                $topic = $this->topicForToken($token);

                try {
                    $packetId = $this->nextPacketId();
                    $packet = MQTT::encodePublish(
                        topic: $topic,
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
                    $ack = $this->readPacket($socket);
                    if ($ack['type'] !== MQTT::PACKET_PUBACK) {
                        $response->addResult($token, 'Broker did not acknowledge PUBLISH');
                        continue;
                    }

                    $parsed = MQTT::parsePuback($ack['payload']);
                    if ($parsed['reasonCode'] !== MQTT::REASON_SUCCESS) {
                        $error = $this->errorForReasonCode($parsed['reasonCode']);
                        $response->addResult($token, $error);
                        continue;
                    }

                    $response->incrementDeliveredTo();
                    $response->addResult($token);
                } catch (\Throwable $error) {
                    $response->addResult($token, $error->getMessage());
                }
            }

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

        return \json_encode($envelope, JSON_UNESCAPED_SLASHES);
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
        $buffer = '';
        while (true) {
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

            $buffer .= $chunk;

            $packet = MQTT::decodePacket($buffer);
            if ($packet !== null) {
                return $packet;
            }
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
