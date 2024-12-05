<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\APNS as APNSAdapter;

class APNSTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $authKey = \getenv('APNS_AUTHKEY_8KVVCLA3HL');
        $authKeyId = \getenv('APNS_AUTH_ID');
        $teamId = \getenv('APNS_TEAM_ID');
        $bundleId = \getenv('APNS_BUNDLE_ID');

        $this->adapter = new APNSAdapter(
            $authKey,
            $authKeyId,
            $teamId,
            $bundleId,
            sandbox: true
        );
    }

    protected function getTo(): array
    {
        return [\getenv('APNS_TO')];
    }
}
