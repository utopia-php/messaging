<?php

namespace Tests\E2E;

class BulkSMSTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $this->markTestSkipped('Bulksms requires you to create an account in order to enable bulk SMS');
    }
}
