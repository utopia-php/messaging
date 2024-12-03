<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\APNS as APNSAdapter;
use Utopia\Messaging\Messages\Push;
use Utopia\Tests\Adapter\Base;

class APNSTest extends Base
{
    public function testSend(): void
    {
        $authKey = \getenv('APNS_AUTHKEY_8KVVCLA3HL');
        $authKeyId = \getenv('APNS_AUTH_ID');
        $teamId = \getenv('APNS_TEAM_ID');
        $bundleId = \getenv('APNS_BUNDLE_ID');

        $adapter = new APNSAdapter($authKey, $authKeyId, $teamId, $bundleId, true);

        $message = new Push(
            to: [\getenv('APNS_TO')],
            title: 'Test title',
            body: 'Test body',
            data: null,
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: 1,
            contentAvailable: true
        );

        $response = $adapter->send($message);

        $this->assertResponse($response);
    }
}
