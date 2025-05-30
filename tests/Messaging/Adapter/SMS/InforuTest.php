<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Inforu;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class InforuTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $sender = new Inforu(
            senderId: \getenv('INFORU_SENDER_ID'),
            apiToken: \getenv('INFORU_API_TOKEN'),
        );

        $message = new SMS(
            to: ['0541234567'],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
