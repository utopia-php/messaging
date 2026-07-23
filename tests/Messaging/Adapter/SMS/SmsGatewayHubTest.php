<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\SmsGatewayHub;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SmsGatewayHubTest extends Base
{
    public function testSendSMS(): void
    {
        $apiKey = \getenv('SMS_GATEWAY_APIKEY');
        $senderId = \getenv('SMS_GATEWAY_SENDER_ID');
        $route = \getenv('SMS_GATEWAY_ROUTE');
        $dltTemplateId = \getenv('SMS_GATEWAY_DLT_TEMPLATE_ID');
        $peId = \getenv('SMS_GATEWAY_PEID');
        $to = \getenv('SMS_GATEWAY_TO');

        if (!$apiKey || !$senderId || !$route || !$dltTemplateId || !$peId || !$to) {
            $this->markTestSkipped('SMSGatewayHub credentials not configured');
        }

        $sender = new SmsGatewayHub(
            apiKey: $apiKey,
            senderId: $senderId,
            route: $route,
            dltTemplateId: $dltTemplateId,
            peId: $peId,
        );

        $message = new SMS(
            to: [$to],
            content: 'Your login OTP is 789456.',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
