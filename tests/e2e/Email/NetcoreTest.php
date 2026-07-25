<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Netcore;
use Utopia\Messaging\Messages\Email;
use Utopia\Tests\Adapter\Base;

class NetcoreTest extends Base
{
    public function testSendEmail(): void
    {
        $sender = new Netcore(\getenv('NETCORE_API_KEY'));

        $message = new Email(
            to: [\getenv('TEST_EMAIL')],
            subject: 'Test Subject',
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
