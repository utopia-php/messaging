<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Push;
use Utopia\Messaging\Priority;
use Utopia\Tests\Adapter\Base as TestBase;

abstract class Base extends TestBase
{
    protected Adapter $adapter;

    /**
     * @return array<string>
     */
    abstract protected function getTo(): array;

    public function testSend(): void
    {
        $message = new Push(
            to: $this->getTo(),
            title: 'Test title',
            body: 'Test body',
            data: null,
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: 1,
        );

        $response = $this->adapter->send($message);

        $this->assertResponse($response);
    }

    public function testSendSilent(): void
    {
        $message = new Push(
            to: $this->getTo(),
            data: [
                'key' => 'value',
            ],
            contentAvailable: true
        );

        $response = $this->adapter->send($message);

        $this->assertResponse($response);
    }

    public function testSendCritical(): void
    {
        $message = new Push(
            to: $this->getTo(),
            title: 'Test title',
            body: 'Test body',
            critical: true
        );

        $response = $this->adapter->send($message);

        $this->assertResponse($response);
    }

    public function testSendPriority(): void
    {
        $message = new Push(
            to: $this->getTo(),
            title: 'Test title',
            body: 'Test body',
            priority: Priority::HIGH
        );

        $response = $this->adapter->send($message);

        $this->assertResponse($response);
    }
}
