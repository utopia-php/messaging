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

        $adapter = new APNSAdapter($authKey, $authKeyId, $teamId, $bundleId);

        $message = new Push(
            to: [\getenv('APNS_TO')],
            title: 'TestTitle',
            body: 'TestBody',
            data: null,
            action: null,
            sound: 'default',
            icon: null,
            color: null,
            tag: null,
            badge: '1'
        );

        $responses = \json_decode($adapter->send($message));

        foreach ($responses as $response) {
            $this->assertEquals('success', $response->status);
        }
    }
}
