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
        $sender = new Taqnyat(\getenv('TAQNYAT_API_KEY'), \getenv('TAQNYAT_SENDER_ID'));

        $message = new SMS(
            to: [\getenv('TAQNYAT_TO')],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
