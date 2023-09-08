<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\APNS as APNSAdapter;
use Utopia\Messaging\Messages\Push;

class APNSTest extends Base
{
    public function testSend(): void
    {
        $authKey = getenv('APNS_AUTHKEY_8KVVCLA3HL');
        $authKeyId = getenv('APNS_AUTH_ID');
        $teamId = getenv('APNS_TEAM_ID');
        $bundleId = getenv('APNS_BUNDLE_ID');
        $endpoint = 'https://api.sandbox.push.apple.com:443';

        $adapter = new APNSAdapter($authKey, $authKeyId, $teamId, $bundleId, $endpoint);

        $message = new Push(
            to: [getenv("APNS_TO")],
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

        $response = (array) \json_decode($adapter->send($message));

        $this->assertEquals('200', $response['status']);
        $this->assertArrayHasKey('apns-id', $response);
        $this->assertArrayHasKey('apns-unique-id', $response);
    }
}
