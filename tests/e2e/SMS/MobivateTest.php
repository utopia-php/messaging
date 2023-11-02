<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Mobivate;
use Utopia\Messaging\Messages\SMS;

class MobivateTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $sender = new Mobivate(getenv('MOBIVATE_API_KEY'), getenv('MOBIVATE_ROUTE_ID'));
        $to = [getenv('MOBIVATE_TO')];
        $from = getenv('MOBIVATE_FROM');

        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $result = \json_decode($sender->send($message));

        $this->assertNotEmpty($result);
        $this->assertCount(\count($to), $result->recipients);
        $this->assertEquals($result->routeId, getenv('MOBIVATE_ROUTE_ID'));
    }
}
