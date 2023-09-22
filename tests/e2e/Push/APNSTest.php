<?php

namespace Tests\E2E;

use Exception;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
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
            to: [getenv('APNS_TO')],
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

    public function testAPNSBenchmark()
    {
        $authKey = getenv('APNS_AUTHKEY_8KVVCLA3HL');
        $authKeyId = getenv('APNS_AUTH_ID');
        $teamId = getenv('APNS_TEAM_ID');
        $bundleId = getenv('APNS_BUNDLE_ID');
        $endpoint = 'https://api.sandbox.push.apple.com:443';

        $adapter = new APNSAdapter($authKey, $authKeyId, $teamId, $bundleId, $endpoint);

        $message = new Push(
            to: [getenv('APNS_TO')],
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

        $start = microtime(true);

        for ($i = 0; $i < 5000; $i++) {
            $adapter->send($message);
        }

        $end = microtime(true);

        $time = floor(($end - $start) * 1000);

        var_dump($time);
        die;

        $this->assertLessThan(3000, $time);
    }
}
