<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\VonageMessages;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class VonageMessagesTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $apiKey = \getenv('VONAGE_API_KEY');
        $apiSecret = \getenv('VONAGE_API_SECRET');

        if (!$apiKey || !$apiSecret) {
            $this->markTestSkipped('Vonage Messages credentials are not available.');
        }

        $sender = new VonageMessages(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            from: \getenv('VONAGE_FROM') ?: 'Vonage',
        );

        $message = new SMS(
            to: [\getenv('VONAGE_TO')],
            content: 'Test Content',
            from: \getenv('VONAGE_FROM')
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
