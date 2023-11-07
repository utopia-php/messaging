<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Adapters\SMS\GEOSMS;
use Utopia\Messaging\Messages\SMS;

class GEOSMSTest extends Base
{
    public function testSendSMSUsingDefaultAdapter()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getMaxMessagesPerRequest')
            ->willReturn(1);
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

        $result = json_decode($adapter->send($message));

        $this->assertEquals('success', $result->status);
    }

    public function testSendSMSUsingLocalAdapter()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getMaxMessagesPerRequest')
            ->willReturn(1);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getMaxMessagesPerRequest')
            ->willReturn(1);
        $localAdapterMock->method('send')
            ->willReturn(json_encode(['status' => 'success', 'adapter' => 'local']));

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal('44', $localAdapterMock);

        $to = ['+441234567890'];
        $from = 'Sender';

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = json_decode($adapter->send($message));

        $this->assertEquals('success', $result->status);
        $this->assertEquals('local', $result->adapter);
    }

    public function testMaxMessagesPerRequestIsLowest()
    {
        $defaultAdapterMock = $this->createMock(SMSAdapter::class);
        $defaultAdapterMock->method('getMaxMessagesPerRequest')
            ->willReturn(1000);
        $localAdapterMock = $this->createMock(SMSAdapter::class);
        $localAdapterMock->method('getMaxMessagesPerRequest')
            ->willReturn(2);

        $adapter = new GEOSMS($defaultAdapterMock);
        $adapter->setLocal('44', $localAdapterMock);

        $this->assertEquals(2, $adapter->getMaxMessagesPerRequest());
    }
}
