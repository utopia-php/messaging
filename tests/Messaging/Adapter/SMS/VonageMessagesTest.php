<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\VonageMessages;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class VonageMessagesTest extends Base
{
    public function testSendSMS(): void
    {
        $apiKey = \getenv('VONAGE_API_KEY');
        $apiSecret = \getenv('VONAGE_API_SECRET');
        $to = \getenv('VONAGE_TO');

        if (!$apiKey || !$apiSecret || !$to) {
            $this->markTestSkipped('Vonage Messages credentials or recipient are not available.');
        }

        $sender = new VonageMessages(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            from: \getenv('VONAGE_FROM') ?: 'Vonage',
        );

        $message = new SMS(
            to: [$to],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendSMSWithFallbackFrom(): void
    {
        $apiKey = \getenv('VONAGE_API_KEY');
        $apiSecret = \getenv('VONAGE_API_SECRET');
        $to = \getenv('VONAGE_TO');
        $from = \getenv('VONAGE_FROM') ?: null;

        if (!$apiKey || !$apiSecret || !$to || !$from) {
            $this->markTestSkipped('Vonage Messages credentials or sender/recipient are not available.');
        }

        $sender = new VonageMessages(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
        );

        $message = new SMS(
            to: [$to],
            content: 'Test Content',
            from: $from,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
