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
            username: \getenv('INFORU_USERNAME'),
            apiToken: \getenv('INFORU_API_TOKEN'),
            sender: \getenv('INFORU_SENDER')
        );

        $message = new SMS(
            to: [\getenv('INFORU_TO')],
            content: 'Test Content'
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
