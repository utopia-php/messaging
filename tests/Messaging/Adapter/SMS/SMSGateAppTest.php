<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\SMSGateApp;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SMSGateAppTest extends Base {
    /**
     * Test sending SMS message with SMSGateApp
     */
    public function testSendSMS(): void {
        // Environment variables for credentials
        $username = \getenv('SMSGATEAPP_USERNAME');
        $password = \getenv('SMSGATEAPP_PASSWORD');
        $to = \getenv('SMSGATEAPP_TO');

        if (!$username || !$password || !$to) {
            $this->markTestSkipped('SMSGateApp credentials not configured');
        }

        // Optional API endpoint if set
        $endpoint = \getenv('SMSGATEAPP_ENDPOINT') ?? null;

        // Instantiate SMSGateApp
        $sender = new SMSGateApp($username, $password, $endpoint);

        // Create SMS message with required 'to' parameter
        $message = new SMS(
            to: [$to],
            content: 'Test content from SMSGateApp'
        );

        // Call send() and verify response
        $response = $sender->send($message);

        // Assertion to match expected response formatting
        $this->assertResponse($response);
    }
}