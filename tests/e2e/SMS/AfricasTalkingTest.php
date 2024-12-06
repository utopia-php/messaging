<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\AfricasTalking;
use Utopia\Messaging\Messages\SMS;

class AfricasTalkingTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        // username is sandbox for sandbox env
        $username = getenv('AFRICASTALKING_USERNAME');
        $apiKey = getenv('AFRICASTALKING_API_KEY');
        
        $sender = new AfricasTalking($username, $apiKey);

        $message = new SMS(
            to: [getenv('SMS_TO')],
            // to must be a comma separated string of recipients' phone numbers.
            content: 'Test Content',
            // from: getenv('SMS_FROM'), optional - https://developers.africastalking.com/docs/sms/sending/bulk
            // defaults to AFRICASTKNG.
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertNotEmpty($result);
    }
}