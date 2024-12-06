<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Semaphore;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class SemaphoreTest extends Base
{
    public function testSendSMS(): void
    {
        $sender = new Semaphore(getenv('SEMAPHORE_API_KEY'));

        $message = new SMS(
            [getenv('SEMAPHORE_TO')],
            'Test Content',
            getenv('SEMAPHORE_SENDER_NAME')
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
