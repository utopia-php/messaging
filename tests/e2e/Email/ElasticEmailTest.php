<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\ElasticEmail;
use Utopia\Messaging\Messages\Email;

class ElasticEmail extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('ElasticEmail_API_KEY');

        $sender = new ElasticEmail(
            apiKey: $key,

        );

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'sender@'.$domain;

        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content,
        );

        $result = (array) \json_decode($sender->send($message));

        $this->assertArrayHasKey('transactionId', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue(str_contains(strtolower($result['message']), 'queued'));
    }
}
