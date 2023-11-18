<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\FortySixElks;
use Utopia\Messaging\Messages\SMS;

class FortySixElksTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        // $sender = new FortySixElks($apiKey, $apiSecret);

        // $message = new SMS(
        //     to: [getenv('FortySixElks_TO')],
        //     content: 'Test Content',
        //     from: getenv('FortySixElks_FROM')
        // );

        // $response = $sender->send($message);

        // $result = \json_decode($response, true);

        // $this->assertArrayHasKey('messages', $result);
        // $this->assertEquals(1, count($result['messages']));
        // $this->assertEquals('1', $result['message-count']);

        $this->markTestSkipped('FortySixElks had no testing numbers available at this time.');
    }
}