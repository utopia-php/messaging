<?php

namespace Utopia\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Helpers\MQTT;

class MQTTTest extends TestCase
{
    public function testEncodesAndParsesConnect(): void
    {
        $packet = MQTT::encodeConnect(
            clientId: 'device-abc',
            username: 'server',
            password: 'jwt.value.here',
            keepAlive: 30,
            cleanStart: true,
            properties: ['sessionExpiryInterval' => 3600],
        );

        $this->assertNotEmpty($packet);
        $this->assertSame(MQTT::PACKET_CONNECT, (\ord($packet[0]) >> 4) & 0x0F);

        $decoded = MQTT::decodePacket($packet);
        $this->assertNotNull($decoded);
        $this->assertSame(MQTT::PACKET_CONNECT, $decoded['type']);

        $parsed = MQTT::parseConnect($decoded['payload']);
        $this->assertSame('MQTT', $parsed['protocol']);
        $this->assertSame(5, $parsed['version']);
        $this->assertSame('device-abc', $parsed['clientId']);
        $this->assertSame('server', $parsed['username']);
        $this->assertSame('jwt.value.here', $parsed['password']);
        $this->assertSame(30, $parsed['keepAlive']);
        $this->assertTrue($parsed['cleanStart']);
        $this->assertSame(3600, $parsed['properties']['sessionExpiryInterval']);
    }

    public function testEncodesAndParsesPublish(): void
    {
        $payload = '{"notification":{"title":"Hi"}}';
        $packet = MQTT::encodePublish(
            topic: 'appwrite/push/device-token-1',
            payload: $payload,
            qos: 1,
            retain: false,
            dup: false,
            packetId: 17,
            properties: [
                'messageExpiryInterval' => 86400,
                'contentType' => 'application/json',
            ],
        );

        $decoded = MQTT::decodePacket($packet);
        $this->assertNotNull($decoded);
        $this->assertSame(MQTT::PACKET_PUBLISH, $decoded['type']);

        $parsed = MQTT::parsePublish($decoded['payload'], $decoded['flags']);
        $this->assertSame('appwrite/push/device-token-1', $parsed['topic']);
        $this->assertSame($payload, $parsed['payload']);
        $this->assertSame(1, $parsed['qos']);
        $this->assertSame(17, $parsed['packetId']);
        $this->assertSame(86400, $parsed['properties']['messageExpiryInterval']);
        $this->assertSame('application/json', $parsed['properties']['contentType']);
    }

    public function testEncodesAndParsesConnack(): void
    {
        $packet = MQTT::encodeConnack(MQTT::REASON_SUCCESS, sessionPresent: false, properties: ['serverKeepAlive' => 60]);
        $decoded = MQTT::decodePacket($packet);
        $this->assertNotNull($decoded);
        $this->assertSame(MQTT::PACKET_CONNACK, $decoded['type']);

        $parsed = MQTT::parseConnack($decoded['payload']);
        $this->assertSame(MQTT::REASON_SUCCESS, $parsed['reasonCode']);
        $this->assertFalse($parsed['sessionPresent']);
        $this->assertSame(60, $parsed['properties']['serverKeepAlive']);
    }

    public function testEncodesAndParsesPuback(): void
    {
        $packet = MQTT::encodePuback(42, MQTT::REASON_SUCCESS);
        $decoded = MQTT::decodePacket($packet);
        $this->assertNotNull($decoded);
        $this->assertSame(MQTT::PACKET_PUBACK, $decoded['type']);

        $parsed = MQTT::parsePuback($decoded['payload']);
        $this->assertSame(42, $parsed['packetId']);
        $this->assertSame(MQTT::REASON_SUCCESS, $parsed['reasonCode']);
    }

    public function testEncodesPingreqAndPingresp(): void
    {
        $req = MQTT::encodePingreq();
        $resp = MQTT::encodePingresp();

        $decodedReq = MQTT::decodePacket($req);
        $decodedResp = MQTT::decodePacket($resp);

        $this->assertSame(MQTT::PACKET_PINGREQ, $decodedReq['type']);
        $this->assertSame(MQTT::PACKET_PINGRESP, $decodedResp['type']);
    }

    public function testDecodeReturnsNullForPartialBuffer(): void
    {
        $packet = MQTT::encodePublish('topic', 'body', 0, false, false, null);
        $partial = \substr($packet, 0, 1);

        $buffer = $partial;
        $this->assertNull(MQTT::decodePacket($buffer));
        $this->assertSame($partial, $buffer);
    }

    public function testDecodeConsumesExactlyOnePacketFromConcatenated(): void
    {
        $first = MQTT::encodePublish('a/b', '1', 0, false, false, null);
        $second = MQTT::encodePublish('c/d', '2', 0, false, false, null);

        $buffer = $first . $second;

        $packet = MQTT::decodePacket($buffer);
        $this->assertNotNull($packet);
        $parsed = MQTT::parsePublish($packet['payload'], $packet['flags']);
        $this->assertSame('a/b', $parsed['topic']);
        $this->assertSame('1', $parsed['payload']);

        $next = MQTT::decodePacket($buffer);
        $this->assertNotNull($next);
        $parsedNext = MQTT::parsePublish($next['payload'], $next['flags']);
        $this->assertSame('c/d', $parsedNext['topic']);
        $this->assertSame('2', $parsedNext['payload']);

        $this->assertSame('', $buffer);
    }

    public function testEncodesLargePayloadAcrossMultiByteRemainingLength(): void
    {
        $bigPayload = \str_repeat('x', 200);
        $packet = MQTT::encodePublish('topic/large', $bigPayload, 0, false, false, null);

        $decoded = MQTT::decodePacket($packet);
        $this->assertNotNull($decoded);
        $parsed = MQTT::parsePublish($decoded['payload'], $decoded['flags']);
        $this->assertSame($bigPayload, $parsed['payload']);
    }

    public function testSubscribeParsing(): void
    {
        $topic = 'appwrite/push/device-abc';
        $body = \pack('n', 5)
            . \chr(0)
            . \pack('n', \strlen($topic)) . $topic
            . \chr(0x01);

        $parsed = MQTT::parseSubscribe($body);

        $this->assertSame(5, $parsed['packetId']);
        $this->assertCount(1, $parsed['filters']);
        $this->assertSame('appwrite/push/device-abc', $parsed['filters'][0]['topic']);
        $this->assertSame(1, $parsed['filters'][0]['qos']);
    }

    public function testEncodeConnectRejectsLongStrings(): void
    {
        $tooLong = \str_repeat('a', 65536);

        $this->expectException(\Throwable::class);
        MQTT::encodeConnect(clientId: $tooLong);
    }
}
