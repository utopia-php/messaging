<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Tests\Adapter\Base;

class TelesignTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendSMS(): void
    {
        $this->markTestSkipped('Telesign requires support/sales call in order to enable bulk SMS');
    }
}
