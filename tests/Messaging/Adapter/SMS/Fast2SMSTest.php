<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Fast2SMS;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class Fast2SMSTest extends Base
{
    /**
     * Test Quick SMS route
     */
    public function testQuickSMS(): void
    {
        $sender = new Fast2SMS(
            apiKey: getenv('FAST2SMS_API_KEY'),
            useDLT: false
        );

        $message = new SMS(
            to: [getenv('FAST2SMS_TO')],
            content: 'Test Quick SMS Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    /**
     * Test DLT route
     */
    public function testDLTSMS(): void
    {
        $sender = new Fast2SMS(
            apiKey: getenv('FAST2SMS_API_KEY'),
            senderId: getenv('FAST2SMS_SENDER_ID'),
            messageId: getenv('FAST2SMS_MESSAGE_ID'),
            variableValues: ['12345'],
            useDLT: true
        );

        $message = new SMS(
            to: [getenv('FAST2SMS_TO')],
            content: '', // Content is ignored when using DLT based messaging
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
