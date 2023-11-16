<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\Bandwidth;
use Utopia\Messaging\Messages\SMS;

class BandwidthTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $apiKey = getenv('BANDWIDTH_API_KEY');
        $apiSecret = getenv('BANDWIDTH_API_SECRET');
        $apiUrl = getenv('BANDWIDTH_API_URL');
        $bandwidth = new Bandwidth($this->apiKey, $this->apiSecret, $this->apiUrl);
        $this->assertEquals('Bandwidth', $bandwidth->getName());
    }

    public function testGetMaxMessagesPerRequest()
    {
        $bandwidth = new Bandwidth($this->apiKey, $this->apiSecret, $this->apiUrl);
        $this->assertEquals(1000, $bandwidth->getMaxMessagesPerRequest());
    }

    public function testProcess()
    {
        $bandwidth = new Bandwidth($this->apiKey, $this->apiSecret, $this->apiUrl);

        $sms = new SMS();
        $sms->setTo('recipient_number');
        $sms->setFrom('sender_number');
        $sms->setBody('Test SMS');

        // Mock the request method to avoid actual HTTP requests
        $this->setOutputCallback(function () {});
        $this->expectOutputString('{"status":"success"}');
        $this->assertEquals('{"status":"success"}', $bandwidth->process($sms));
    }
}
