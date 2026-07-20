<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Taqnyat;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class TaqnyatTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        // Environment variables
        $apiKey = \getenv('TAQNYAT_API_KEY');
        $senderId = \getenv('TAQNYAT_SENDER_ID');
        $to = \getenv('TAQNYAT_TO');

        if (!$apiKey || !$senderId || !$to) {
            $this->markTestSkipped('TAQNYAT credentials not configured');
        }

        $sender = new Taqnyat(
            apiKey: $apiKey,
            senderId: $senderId
        );

        $message = new SMS(
            to: [$to],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
