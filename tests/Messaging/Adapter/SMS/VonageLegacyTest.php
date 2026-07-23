<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\VonageLegacy;
use Utopia\Tests\Adapter\Base;

class VonageLegacyTest extends Base
{
    public function testSendSMS(): void
    {
        $this->markTestSkipped('Vonage credentials are not available.');
    }
}
