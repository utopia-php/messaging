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
            to: ['80858be082476f1067ce737b69240bbea9b58676d0eef64960f3aa75b6cb7ca7656822f02a56960ff805b393ab7c82484f56229b69731f939ae8c3aa27399c29fe8efa26d272b5a1817813f023dee9fd'],
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

        $response = $adapter->send($message);

        $this->assertEquals('', $response);
    }
}
