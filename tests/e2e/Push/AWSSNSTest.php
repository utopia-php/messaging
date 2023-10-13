<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Push\AWSSNS as AWSSNSAdapter;
use Utopia\Messaging\Messages\Push;

class AWSSNSTest extends Base
{
    public function testSend(): void
    {
        $apiGatewayUrl = getenv('AWSSNS_API_GATEWAY_URL');

        $adapter = new AWSSNSAdapter($apiGatewayUrl);

        $message = new Push(
            data: null
        );

        $response = json_decode($adapter->send($message), true);

        $this->assertNotEmpty($response);
        $this->assertEquals('success', $response['status'] ?? null);
        $this->assertNotEmpty($response['MessageId'] ?? null);
    }
}
