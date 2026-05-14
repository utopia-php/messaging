<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\AfricasTalking;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class AfricasTalkingTest extends Base
{
    public function testSendSMS(): void
    {
        $username = \getenv('AFRICAS_TALKING_USERNAME');
        $apiKey = \getenv('AFRICAS_TALKING_API_KEY');
        $to = \getenv('AFRICAS_TALKING_TO');

        if (empty($username) || empty($apiKey) || empty($to)) {
            $this->markTestSkipped('AfricasTalking credentials are not available.');
        }

        $sender = new AfricasTalking($username, $apiKey);

        $message = new SMS(
            to: [$to],
            content: 'Test Content',
            from: \getenv('AFRICAS_TALKING_FROM') ?: null
        );

        $response = $sender->send($message);

        $result = \json_decode($response, true);

        $this->assertResponse($result);
    }

    public function testSendSMSWithFrom(): void
    {
        $username = \getenv('AFRICAS_TALKING_USERNAME');
        $apiKey = \getenv('AFRICAS_TALKING_API_KEY');
        $to = \getenv('AFRICAS_TALKING_TO');
        $from = \getenv('AFRICAS_TALKING_FROM');

        if (empty($username) || empty($apiKey) || empty($to)) {
            $this->markTestSkipped('AfricasTalking credentials are not available.');
        }

        $sender = new AfricasTalking($username, $apiKey, $from ?: 'AFRICAST');

        $message = new SMS(
            to: [$to],
            content: 'Test Content with custom from'
        );

        $response = $sender->send($message);

        $result = \json_decode($response, true);

        $this->assertResponse($result);
    }
}