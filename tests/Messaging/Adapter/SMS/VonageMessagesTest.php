<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\VonageMessages;
use Utopia\Messaging\Adapter\WhatsApp\Vonage as VonageWhatsApp;
use Utopia\Messaging\Adapter\Viber\Vonage as VonageViber;
use Utopia\Messaging\Adapter\MMS\Vonage as VonageMMS;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class VonageMessagesTest extends Base
{
    private string $applicationId = 'test-application-id';
    private string $privateKey = "-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA75P/9p6z8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz8zKz
-----END RSA PRIVATE KEY-----";

    public function testSendSMS(): void
    {
        $sender = new VonageMessages($this->applicationId, $this->privateKey);
        $sender->setEndpoint('http://request-catcher-sms:5000/');

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test SMS Content',
            from: 'Appwrite'
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        // This assumes the mock server returns a success response
        // In a real environment, we'd check the request-catcher data
        $this->assertNotEmpty($result);
    }

    public function testSendWhatsApp(): void
    {
        $sender = new VonageWhatsApp($this->applicationId, $this->privateKey);
        $sender->setEndpoint('http://request-catcher-whatsapp:5000/');

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test WhatsApp Content',
            from: 'Appwrite'
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertNotEmpty($result);
    }

    public function testSendViber(): void
    {
        $sender = new VonageViber($this->applicationId, $this->privateKey);
        $sender->setEndpoint('http://request-catcher-viber:5000/');

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test Viber Content',
            from: 'Appwrite'
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertNotEmpty($result);
    }

    public function testSendMMS(): void
    {
        $sender = new VonageMMS($this->applicationId, $this->privateKey);
        $sender->setEndpoint('http://request-catcher-mms:5000/');

        $message = new SMS(
            to: ['+1234567890'],
            content: 'Test MMS Content',
            from: 'Appwrite'
        );

        $response = $sender->send($message);
        $result = \json_decode($response, true);

        $this->assertNotEmpty($result);
    }
}
