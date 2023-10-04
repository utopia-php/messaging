<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\SmsGlobal;
use Utopia\Messaging\Messages\SMS;

class SmsGlobalTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $apiKey = getenv('SMS_GLOBAL_API_KEY');
        $apiSecret = getenv('SMS_GLOBAL_API_SECRET');

        $to = [getenv('SMS_GLOBAL_TO')];
        $from = getenv('SMS_GLOBAL_FROM');
        
        $sender = new SmsGlobal($apiKey, $apiSecret);
        $message = new SMS(
            to: $to,
            content: 'Test Content',
            from: $from
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertArrayHasKey('messages', $result);
        $this->assertEquals(count($to), count($result['messages']));

        // $dummyResponseStructure = '{"messages":[{"id":"154","outgoing_id":1,"origin":"origin","destination":"destination","message":"Test Content","status":"sent","dateTime":""}]}';
    }
}
