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
            apiToken: \getenv('INFORU_API_TOKEN'),
            sender: \getenv('INFORU_SENDER')
        );

        $message = new SMS(
            to: ['0541234567'],
            content: 'Test Content',
            from: '+987654321'
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
