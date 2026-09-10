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
        $this->markTestSkipped('Vonage Messages credentials are not available.');

        /*
            $apiKey = \getenv('VONAGE_MESSAGES_API_KEY');
            $apiSecret = \getenv('VONAGE_MESSAGES_API_SECRET');

            $sender = new VonageMessages($apiKey, $apiSecret);

            $message = new SMS(
                to: [\getenv('VONAGE_MESSAGES_TO')],
                content: 'Test Content',
                from: \getenv('VONAGE_MESSAGES_FROM'),
            );

            $response = $sender->send($message);

            $this->assertNotEmpty($response['results']);
            $this->assertNotEmpty($response['results'][0]['success']);
        */
    }
}
