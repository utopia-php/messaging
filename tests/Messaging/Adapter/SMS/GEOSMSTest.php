<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Adapter\SMS\GEOSMS;
use Utopia\Messaging\Adapter\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class GEOSMSTest extends Base
{
    public function testSendSMSUsingDefaultAdapter(): void
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')->willReturn('default');
        $defaultAdapterMock->method('send')->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);

        $to = ['+11234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = $adapter->send($message);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['default']['results'][0]['status']);
    }

    public function testSendSMSUsingLocalAdapter(): void
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')->willReturn('local');
        $localAdapterMock->method('send')->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);

        $to = ['+911234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = $adapter->send($message);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['local']['results'][0]['status']);
    }

    public function testSendSMSUsingLocalAdapterAndDefault(): void
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')->willReturn('default');
        $defaultAdapterMock->method('send')->willReturn(['results' => [['status' => 'success']]]);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')->willReturn('local');
        $localAdapterMock->method('send')->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);

        $to = ['+911234567890', '+11234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = $adapter->send($message);

        $this->assertEquals(2, count($result));
        $this->assertEquals('success', $result['local']['results'][0]['status']);
        $this->assertEquals('success', $result['default']['results'][0]['status']);
    }

    public function testSendSMSUsingGroupedLocalAdapter(): void
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')->willReturn('local');
        $localAdapterMock->method('send')->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);
        $adapter->setLocal(CallingCode::NORTH_AMERICA, $localAdapterMock);

        $to = ['+911234567890', '+11234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = $adapter->send($message);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['local']['results'][0]['status']);
    }

    public function testSendSMSForwardsMetadata(): void
    {
        $metadata = [
            'clientId' => 'client-123',
            'CRQID' => 'request_123',
            'UUID' => 'uuid.123',
        ];

        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')->willReturn('default');
        $defaultAdapterMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (SMS $message) use ($metadata): bool {
                return $message->getMetadata() === $metadata;
            }))
            ->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);

        $message = new SMS(
            to: ['+11234567890'],
            content: 'Test Content',
            metadata: $metadata
        );

        $result = $adapter->send($message);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['default']['results'][0]['status']);
    }

    public function testSendSMSRemovesRequestTrackingMetadataWhenSplit(): void
    {
        $metadata = [
            'clientId' => 'client-123',
            'CRQID' => 'request_123',
            'UUID' => 'uuid.123',
        ];

        $expectedMetadata = [
            'clientId' => 'client-123',
        ];

        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')->willReturn('default');
        $defaultAdapterMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (SMS $message) use ($expectedMetadata): bool {
                return $message->getMetadata() === $expectedMetadata;
            }))
            ->willReturn(['results' => [['status' => 'success']]]);

        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')->willReturn('local');
        $localAdapterMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (SMS $message) use ($expectedMetadata): bool {
                return $message->getMetadata() === $expectedMetadata;
            }))
            ->willReturn(['results' => [['status' => 'success']]]);

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);

        $message = new SMS(
            to: ['+911234567890', '+11234567890'],
            content: 'Test Content',
            metadata: $metadata
        );

        $result = $adapter->send($message);

        $this->assertEquals(2, count($result));
        $this->assertEquals('success', $result['local']['results'][0]['status']);
        $this->assertEquals('success', $result['default']['results'][0]['status']);
    }
}
