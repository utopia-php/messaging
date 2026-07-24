<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\OneSignal as OneSignalAdapter;

class OneSignalTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $appId = \getenv('ONESIGNAL_APP_ID');
        $apiKey = \getenv('ONESIGNAL_API_KEY');

        $this->adapter = new OneSignalAdapter($appId, $apiKey);
    }

    protected function getTo(): array
    {
        return [\getenv('ONESIGNAL_TO')];
    }
}
