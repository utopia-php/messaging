<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\FCM as FCMAdapter;

class FCMTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $serverKey = \getenv('FCM_SERVICE_ACCOUNT_JSON');

        $this->adapter = new FCMAdapter($serverKey);
    }

    protected function getTo(): array
    {
        return [\getenv('FCM_TO')];
    }
}
