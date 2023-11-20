<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Netcore;
use Utopia\Messaging\Messages\Email;

class NetcoreTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendPlainTextEmail()
    {
        $this->markTestSkipped('Netcore credentials not set.');

        $key = getenv('NETCORE_API_KEY');
        $sender = new Netcore(
            apiKey: $key,
            isEU: false
        );

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $result = (array) \json_decode($sender->send($message));

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('OK', $result['message']);
        $this->assertEquals('success', $result['status']);
    }
}
