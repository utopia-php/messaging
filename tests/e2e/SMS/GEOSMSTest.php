<?php

namespace Tests\E2E\SMS;

use Tests\E2E\Base;
use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Adapters\SMS\GEOSMS;
use Utopia\Messaging\Adapters\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Messages\SMS;

class GEOSMSTest extends Base
{
    public function testSendSMSUsingDefaultAdapter()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')
            ->willReturn('default');
        $defaultAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success']));

        $adapter = new GEOSMS($defaultAdapterMock);

        $to = ['+11234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = json_decode($adapter->send($message), true);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['default']['status']);
    }

    public function testSendSMSUsingLocalAdapter()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')
            ->willReturn('local');
        $localAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success']));

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);

        $to = ['+911234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = json_decode($adapter->send($message), true);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['local']['status']);
    }

    public function testSendSMSUsingLocalAdapterAndDefault()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getName')
            ->willReturn('default');
        $defaultAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success']));
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')
            ->willReturn('local');
        $localAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success']));

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal(CallingCode::INDIA, $localAdapterMock);

        $to = ['+911234567890', '+11234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = json_decode($adapter->send($message), true);

        $this->assertEquals(2, count($result));
        $this->assertEquals('success', $result['local']['status']);
        $this->assertEquals('success', $result['default']['status']);
    }

    public function testSendSMSUsingGroupedLocalAdapter()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getName')
            ->willReturn('local');
        $localAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success']));

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

        $result = json_decode($adapter->send($message), true);

        $this->assertEquals(1, count($result));
        $this->assertEquals('success', $result['local']['status']);
    }
}
