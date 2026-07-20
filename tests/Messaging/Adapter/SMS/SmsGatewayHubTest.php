<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\SMSGatewayHub;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SMSGatewayHubTest extends Base
{
    public function testSendSMS(): void
    {
        $sender = new SMSGatewayHub(
            apiKey: getenv('SMS_GATEWAY_APIKEY'),
            senderId: getenv('SMS_GATEWAY_SENDER_ID'),
            route: getenv('SMS_GATEWAY_ROUTE'),
            dltTemplateId: getenv('SMS_GATEWAY_DLT_TEMPLATE_ID'),
            peId: getenv('SMS_GATEWAY_PEID')
        );

        $message = new SMS(
            to: [917358240825],
            content: 'Your login OTP is 789456. This OTP is Valid for 5 Minutes. Never share your OTP with anyone.',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
