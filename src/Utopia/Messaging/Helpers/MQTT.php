<?php

namespace Utopia\Messaging\Helpers;

/**
 * Minimal MQTT 5.0 control-packet codec.
 *
 * Encodes/decodes the subset of MQTT 5 needed to act as a publisher and as a
 * broker for Appwrite's device-fan-out use case. Designed to be transport
 * agnostic — pass binary blobs in/out, the caller handles sockets.
 */
class MQTT
{
    public const PROTOCOL_NAME = 'MQTT';
    public const PROTOCOL_VERSION = 5;

    public const PACKET_CONNECT = 1;
    public const PACKET_CONNACK = 2;
    public const PACKET_PUBLISH = 3;
    public const PACKET_PUBACK = 4;
    public const PACKET_SUBSCRIBE = 8;
    public const PACKET_SUBACK = 9;
    public const PACKET_UNSUBSCRIBE = 10;
    public const PACKET_UNSUBACK = 11;
    public const PACKET_PINGREQ = 12;
    public const PACKET_PINGRESP = 13;
    public const PACKET_DISCONNECT = 14;
    public const PACKET_AUTH = 15;

    public const REASON_SUCCESS = 0x00;
    public const REASON_UNSPECIFIED = 0x80;
    public const REASON_MALFORMED = 0x81;
    public const REASON_PROTOCOL_ERROR = 0x82;
    public const REASON_NOT_AUTHORIZED = 0x87;
    public const REASON_SERVER_BUSY = 0x89;
    public const REASON_BAD_AUTH = 0x8C;
    public const REASON_TOPIC_INVALID = 0x90;

    public const PROPERTY_MESSAGE_EXPIRY = 0x02;
    public const PROPERTY_CONTENT_TYPE = 0x03;
    public const PROPERTY_RESPONSE_TOPIC = 0x08;
    public const PROPERTY_CORRELATION_DATA = 0x09;
    public const PROPERTY_SESSION_EXPIRY = 0x11;
    public const PROPERTY_RECEIVE_MAXIMUM = 0x21;
    public const PROPERTY_MAXIMUM_QOS = 0x24;
    public const PROPERTY_RETAIN_AVAILABLE = 0x25;
    public const PROPERTY_USER_PROPERTY = 0x26;
    public const PROPERTY_TOPIC_ALIAS_MAXIMUM = 0x22;
    public const PROPERTY_REASON_STRING = 0x1F;
    public const PROPERTY_AUTHENTICATION_METHOD = 0x15;
    public const PROPERTY_AUTHENTICATION_DATA = 0x16;
    public const PROPERTY_WILDCARD_SUBSCRIPTION_AVAILABLE = 0x28;
    public const PROPERTY_SHARED_SUBSCRIPTION_AVAILABLE = 0x2A;
    public const PROPERTY_SERVER_KEEP_ALIVE = 0x13;
    public const PROPERTY_ASSIGNED_CLIENT_ID = 0x12;

    /**
     * Encode a CONNECT packet for MQTT 5.
     *
     * @param array<string, mixed> $properties Extra connection properties (session expiry, etc.).
     */
    public static function encodeConnect(
        string $clientId,
        ?string $username = null,
        ?string $password = null,
        int $keepAlive = 60,
        bool $cleanStart = true,
        array $properties = []
    ): string {
        $variable = self::encodeString(self::PROTOCOL_NAME);
        $variable .= \chr(self::PROTOCOL_VERSION);

        $flags = 0;
        if ($cleanStart) {
            $flags |= 0x02;
        }
        if ($password !== null) {
            if ($username === null) {
                throw new \InvalidArgumentException('MQTT 5 §3.1.2.9 forbids setting a password without a username.');
            }
            $flags |= 0x40;
        }
        if ($username !== null) {
            $flags |= 0x80;
        }

        $variable .= \chr($flags);
        $variable .= \pack('n', $keepAlive);

        $props = '';
        if (isset($properties['sessionExpiryInterval'])) {
            $props .= \chr(self::PROPERTY_SESSION_EXPIRY) . \pack('N', (int)$properties['sessionExpiryInterval']);
        }
        if (isset($properties['authenticationMethod'])) {
            $props .= \chr(self::PROPERTY_AUTHENTICATION_METHOD) . self::encodeString((string)$properties['authenticationMethod']);
        }
        if (isset($properties['authenticationData'])) {
            $props .= \chr(self::PROPERTY_AUTHENTICATION_DATA) . self::encodeBinary((string)$properties['authenticationData']);
        }

        $variable .= self::encodeVariableByteInteger(\strlen($props)) . $props;

        $payload = self::encodeString($clientId);
        if ($username !== null) {
            $payload .= self::encodeString($username);
        }
        if ($password !== null) {
            $payload .= self::encodeBinary($password);
        }

        return self::buildPacket(self::PACKET_CONNECT, 0, $variable . $payload);
    }

    /**
     * Encode a CONNACK packet.
     *
     * @param array<string, mixed> $properties Optional server-keepalive/assigned-client-id etc.
     */
    public static function encodeConnack(int $reasonCode, bool $sessionPresent = false, array $properties = []): string
    {
        $variable = \chr($sessionPresent ? 0x01 : 0x00);
        $variable .= \chr($reasonCode);

        $props = '';
        if (isset($properties['serverKeepAlive'])) {
            $props .= \chr(self::PROPERTY_SERVER_KEEP_ALIVE) . \pack('n', (int)$properties['serverKeepAlive']);
        }
        if (isset($properties['assignedClientId'])) {
            $props .= \chr(self::PROPERTY_ASSIGNED_CLIENT_ID) . self::encodeString((string)$properties['assignedClientId']);
        }
        if (isset($properties['reasonString'])) {
            $props .= \chr(self::PROPERTY_REASON_STRING) . self::encodeString((string)$properties['reasonString']);
        }
        if (isset($properties['maximumQoS'])) {
            $props .= \chr(self::PROPERTY_MAXIMUM_QOS) . \chr((int)$properties['maximumQoS']);
        }
        if (isset($properties['retainAvailable'])) {
            $props .= \chr(self::PROPERTY_RETAIN_AVAILABLE) . \chr($properties['retainAvailable'] ? 1 : 0);
        }
        if (isset($properties['receiveMaximum'])) {
            $props .= \chr(self::PROPERTY_RECEIVE_MAXIMUM) . \pack('n', (int)$properties['receiveMaximum']);
        }
        if (isset($properties['wildcardSubscriptionAvailable'])) {
            $props .= \chr(self::PROPERTY_WILDCARD_SUBSCRIPTION_AVAILABLE) . \chr($properties['wildcardSubscriptionAvailable'] ? 1 : 0);
        }
        if (isset($properties['sharedSubscriptionAvailable'])) {
            $props .= \chr(self::PROPERTY_SHARED_SUBSCRIPTION_AVAILABLE) . \chr($properties['sharedSubscriptionAvailable'] ? 1 : 0);
        }

        $variable .= self::encodeVariableByteInteger(\strlen($props)) . $props;

        return self::buildPacket(self::PACKET_CONNACK, 0, $variable);
    }

    /**
     * Encode a PUBLISH packet.
     *
     * @param array<string, mixed> $properties Optional message expiry, content type, etc.
     */
    public static function encodePublish(
        string $topic,
        string $payload,
        int $qos = 0,
        bool $retain = false,
        bool $dup = false,
        ?int $packetId = null,
        array $properties = []
    ): string {
        if ($qos < 0 || $qos > 2) {
            throw new \InvalidArgumentException("MQTT QoS must be 0, 1, or 2 ({$qos} given)");
        }

        $flags = 0;
        if ($dup) {
            $flags |= 0x08;
        }
        $flags |= $qos << 1;
        if ($retain) {
            $flags |= 0x01;
        }

        $variable = self::encodeString($topic);
        if ($qos > 0) {
            if ($packetId === null) {
                throw new \InvalidArgumentException('packetId is required for QoS > 0');
            }
            $variable .= \pack('n', $packetId);
        }

        $props = '';
        if (isset($properties['messageExpiryInterval'])) {
            $props .= \chr(self::PROPERTY_MESSAGE_EXPIRY) . \pack('N', (int)$properties['messageExpiryInterval']);
        }
        if (isset($properties['contentType'])) {
            $props .= \chr(self::PROPERTY_CONTENT_TYPE) . self::encodeString((string)$properties['contentType']);
        }
        if (isset($properties['correlationData'])) {
            $props .= \chr(self::PROPERTY_CORRELATION_DATA) . self::encodeBinary((string)$properties['correlationData']);
        }
        if (isset($properties['responseTopic'])) {
            $props .= \chr(self::PROPERTY_RESPONSE_TOPIC) . self::encodeString((string)$properties['responseTopic']);
        }
        foreach ($properties['userProperties'] ?? [] as $key => $value) {
            $props .= \chr(self::PROPERTY_USER_PROPERTY) . self::encodeString((string)$key) . self::encodeString((string)$value);
        }

        $variable .= self::encodeVariableByteInteger(\strlen($props)) . $props;

        return self::buildPacket(self::PACKET_PUBLISH, $flags, $variable . $payload);
    }

    /**
     * Encode a PUBACK packet.
     */
    public static function encodePuback(int $packetId, int $reasonCode = self::REASON_SUCCESS): string
    {
        $variable = \pack('n', $packetId);
        $variable .= \chr($reasonCode);
        $variable .= \chr(0);

        return self::buildPacket(self::PACKET_PUBACK, 0, $variable);
    }

    /**
     * Encode a SUBACK packet.
     *
     * @param array<int> $reasonCodes One reason code per topic filter in the SUBSCRIBE.
     */
    public static function encodeSuback(int $packetId, array $reasonCodes): string
    {
        $variable = \pack('n', $packetId);
        $variable .= \chr(0);
        foreach ($reasonCodes as $code) {
            $variable .= \chr($code);
        }

        return self::buildPacket(self::PACKET_SUBACK, 0, $variable);
    }

    /**
     * Encode a PINGRESP packet.
     */
    public static function encodePingresp(): string
    {
        return self::buildPacket(self::PACKET_PINGRESP, 0, '');
    }

    /**
     * Encode a PINGREQ packet.
     */
    public static function encodePingreq(): string
    {
        return self::buildPacket(self::PACKET_PINGREQ, 0, '');
    }

    /**
     * Encode a DISCONNECT packet.
     */
    public static function encodeDisconnect(int $reasonCode = self::REASON_SUCCESS): string
    {
        if ($reasonCode === self::REASON_SUCCESS) {
            return self::buildPacket(self::PACKET_DISCONNECT, 0, '');
        }

        $variable = \chr($reasonCode) . \chr(0);

        return self::buildPacket(self::PACKET_DISCONNECT, 0, $variable);
    }

    /**
     * Decode a single MQTT control packet from a buffer.
     *
     * Returns null if the buffer does not yet contain a full packet. On success
     * advances the &$buffer past the consumed bytes and returns the parsed packet.
     *
     * @return array{type: int, flags: int, payload: string}|null
     */
    public static function decodePacket(string &$buffer): ?array
    {
        $length = \strlen($buffer);
        if ($length < 2) {
            return null;
        }

        $firstByte = \ord($buffer[0]);
        $type = ($firstByte >> 4) & 0x0F;
        $flags = $firstByte & 0x0F;

        $offset = 1;
        $remaining = self::readVariableByteInteger($buffer, $offset);
        if ($remaining === null) {
            return null;
        }

        $total = $offset + $remaining;
        if ($length < $total) {
            return null;
        }

        $payload = \substr($buffer, $offset, $remaining);
        $buffer = \substr($buffer, $total);

        return [
            'type' => $type,
            'flags' => $flags,
            'payload' => $payload,
        ];
    }

    /**
     * Parse a CONNECT packet body (the bytes after the fixed header).
     *
     * @return array{
     *     protocol: string,
     *     version: int,
     *     flags: int,
     *     keepAlive: int,
     *     clientId: string,
     *     username: ?string,
     *     password: ?string,
     *     properties: array<string, mixed>,
     *     cleanStart: bool
     * }
     */
    public static function parseConnect(string $payload): array
    {
        $offset = 0;
        $protocol = self::readString($payload, $offset);
        $version = \ord($payload[$offset++]);
        $flags = \ord($payload[$offset++]);
        $keepAlive = \unpack('n', \substr($payload, $offset, 2))[1];
        $offset += 2;

        $propLen = self::readVariableByteInteger($payload, $offset);
        $props = self::readProperties(\substr($payload, $offset, $propLen));
        $offset += $propLen;

        $clientId = self::readString($payload, $offset);

        $username = null;
        $password = null;

        if ($flags & 0x80) {
            $username = self::readString($payload, $offset);
        }
        if ($flags & 0x40) {
            $password = self::readBinary($payload, $offset);
        }

        return [
            'protocol' => $protocol,
            'version' => $version,
            'flags' => $flags,
            'cleanStart' => (bool)($flags & 0x02),
            'keepAlive' => $keepAlive,
            'clientId' => $clientId,
            'username' => $username,
            'password' => $password,
            'properties' => $props,
        ];
    }

    /**
     * Parse a PUBLISH packet body. $flags is the lower nibble of the fixed header.
     *
     * @return array{
     *     topic: string,
     *     payload: string,
     *     qos: int,
     *     retain: bool,
     *     dup: bool,
     *     packetId: ?int,
     *     properties: array<string, mixed>
     * }
     */
    public static function parsePublish(string $payload, int $flags): array
    {
        $qos = ($flags >> 1) & 0x03;
        $retain = (bool)($flags & 0x01);
        $dup = (bool)($flags & 0x08);

        $offset = 0;
        $topic = self::readString($payload, $offset);

        $packetId = null;
        if ($qos > 0) {
            $packetId = \unpack('n', \substr($payload, $offset, 2))[1];
            $offset += 2;
        }

        $propLen = self::readVariableByteInteger($payload, $offset);
        $props = self::readProperties(\substr($payload, $offset, $propLen));
        $offset += $propLen;

        return [
            'topic' => $topic,
            'payload' => \substr($payload, $offset),
            'qos' => $qos,
            'retain' => $retain,
            'dup' => $dup,
            'packetId' => $packetId,
            'properties' => $props,
        ];
    }

    /**
     * Parse a SUBSCRIBE packet body.
     *
     * @return array{
     *     packetId: int,
     *     filters: array<array{topic: string, qos: int, noLocal: bool, retainAsPublished: bool, retainHandling: int}>
     * }
     */
    public static function parseSubscribe(string $payload): array
    {
        $offset = 0;
        $packetId = \unpack('n', \substr($payload, $offset, 2))[1];
        $offset += 2;

        $propLen = self::readVariableByteInteger($payload, $offset);
        $offset += $propLen;

        $filters = [];
        while ($offset < \strlen($payload)) {
            $topic = self::readString($payload, $offset);
            $options = \ord($payload[$offset++]);
            $filters[] = [
                'topic' => $topic,
                'qos' => $options & 0x03,
                'noLocal' => (bool)($options & 0x04),
                'retainAsPublished' => (bool)($options & 0x08),
                'retainHandling' => ($options >> 4) & 0x03,
            ];
        }

        return [
            'packetId' => $packetId,
            'filters' => $filters,
        ];
    }

    /**
     * Parse a CONNACK packet body.
     *
     * @return array{sessionPresent: bool, reasonCode: int, properties: array<string, mixed>}
     */
    public static function parseConnack(string $payload): array
    {
        $sessionPresent = (bool)(\ord($payload[0]) & 0x01);
        $reasonCode = \ord($payload[1]);
        $offset = 2;
        $propLen = self::readVariableByteInteger($payload, $offset);
        $props = self::readProperties(\substr($payload, $offset, $propLen));

        return [
            'sessionPresent' => $sessionPresent,
            'reasonCode' => $reasonCode,
            'properties' => $props,
        ];
    }

    /**
     * Parse a PUBACK packet body.
     *
     * @return array{packetId: int, reasonCode: int}
     */
    public static function parsePuback(string $payload): array
    {
        $packetId = \unpack('n', \substr($payload, 0, 2))[1];
        $reasonCode = \strlen($payload) > 2 ? \ord($payload[2]) : self::REASON_SUCCESS;

        return [
            'packetId' => $packetId,
            'reasonCode' => $reasonCode,
        ];
    }

    private static function buildPacket(int $type, int $flags, string $body): string
    {
        $header = \chr((($type & 0x0F) << 4) | ($flags & 0x0F));

        return $header . self::encodeVariableByteInteger(\strlen($body)) . $body;
    }

    private static function encodeString(string $value): string
    {
        $length = \strlen($value);
        if ($length > 0xFFFF) {
            throw new \InvalidArgumentException("MQTT string exceeds 65535 byte limit ({$length} given)");
        }

        return \pack('n', $length) . $value;
    }

    private static function encodeBinary(string $value): string
    {
        $length = \strlen($value);
        if ($length > 0xFFFF) {
            throw new \InvalidArgumentException("MQTT binary exceeds 65535 byte limit ({$length} given)");
        }

        return \pack('n', $length) . $value;
    }

    private static function encodeVariableByteInteger(int $value): string
    {
        if ($value < 0 || $value > 268435455) {
            throw new \InvalidArgumentException('Variable byte integer out of range.');
        }

        $bytes = '';
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            if ($value > 0) {
                $byte |= 0x80;
            }
            $bytes .= \chr($byte);
        } while ($value > 0);

        return $bytes;
    }

    private static function readVariableByteInteger(string $buffer, int &$offset): ?int
    {
        $multiplier = 1;
        $value = 0;
        $length = \strlen($buffer);
        $start = $offset;

        do {
            if ($offset >= $length) {
                $offset = $start;
                return null;
            }
            $byte = \ord($buffer[$offset++]);
            $value += ($byte & 0x7F) * $multiplier;
            if ($multiplier > 128 * 128 * 128) {
                throw new \RuntimeException('Malformed variable byte integer');
            }
            $multiplier *= 128;
        } while (($byte & 0x80) !== 0);

        return $value;
    }

    private static function readString(string $buffer, int &$offset): string
    {
        $len = \unpack('n', \substr($buffer, $offset, 2))[1];
        $offset += 2;
        $value = \substr($buffer, $offset, $len);
        $offset += $len;

        return $value;
    }

    private static function readBinary(string $buffer, int &$offset): string
    {
        return self::readString($buffer, $offset);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readProperties(string $buffer): array
    {
        $offset = 0;
        $length = \strlen($buffer);
        $properties = [];

        while ($offset < $length) {
            $identifier = \ord($buffer[$offset++]);

            switch ($identifier) {
                case self::PROPERTY_MESSAGE_EXPIRY:
                    $properties['messageExpiryInterval'] = \unpack('N', \substr($buffer, $offset, 4))[1];
                    $offset += 4;
                    break;
                case self::PROPERTY_CONTENT_TYPE:
                    $properties['contentType'] = self::readString($buffer, $offset);
                    break;
                case self::PROPERTY_RESPONSE_TOPIC:
                    $properties['responseTopic'] = self::readString($buffer, $offset);
                    break;
                case self::PROPERTY_CORRELATION_DATA:
                    $properties['correlationData'] = self::readBinary($buffer, $offset);
                    break;
                case self::PROPERTY_SESSION_EXPIRY:
                    $properties['sessionExpiryInterval'] = \unpack('N', \substr($buffer, $offset, 4))[1];
                    $offset += 4;
                    break;
                case self::PROPERTY_RECEIVE_MAXIMUM:
                    $properties['receiveMaximum'] = \unpack('n', \substr($buffer, $offset, 2))[1];
                    $offset += 2;
                    break;
                case self::PROPERTY_AUTHENTICATION_METHOD:
                    $properties['authenticationMethod'] = self::readString($buffer, $offset);
                    break;
                case self::PROPERTY_AUTHENTICATION_DATA:
                    $properties['authenticationData'] = self::readBinary($buffer, $offset);
                    break;
                case self::PROPERTY_USER_PROPERTY:
                    $key = self::readString($buffer, $offset);
                    $value = self::readString($buffer, $offset);
                    $properties['userProperties'][$key] = $value;
                    break;
                case self::PROPERTY_TOPIC_ALIAS_MAXIMUM:
                    $properties['topicAliasMaximum'] = \unpack('n', \substr($buffer, $offset, 2))[1];
                    $offset += 2;
                    break;
                case self::PROPERTY_SERVER_KEEP_ALIVE:
                    $properties['serverKeepAlive'] = \unpack('n', \substr($buffer, $offset, 2))[1];
                    $offset += 2;
                    break;
                case self::PROPERTY_REASON_STRING:
                    $properties['reasonString'] = self::readString($buffer, $offset);
                    break;
                default:
                    return $properties;
            }
        }

        return $properties;
    }
}
