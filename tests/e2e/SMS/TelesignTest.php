<?php

namespace Tests\E2E\SMS;

use Tests\E2E\Base;

class TelesignTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS()
    {
        $this->markTestSkipped('Telesign requires support/sales call in order to enable bulk SMS');
    }
}
