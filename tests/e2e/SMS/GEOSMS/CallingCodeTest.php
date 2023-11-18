<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\SMS\GEOSMS\CallingCode;

class CallingCodeTest extends Base
{
    public function testFromPhoneNumber()
    {
        $this->assertEquals(CallingCode::NORTH_AMERICA, CallingCode::fromPhoneNumber('+11234567890'));
        $this->assertEquals(CallingCode::INDIA, CallingCode::fromPhoneNumber('+911234567890'));
        $this->assertEquals(CallingCode::ISRAEL, CallingCode::fromPhoneNumber('9721234567890'));
        $this->assertEquals(CallingCode::UNITED_ARAB_EMIRATES, CallingCode::fromPhoneNumber('009711234567890'));
        $this->assertEquals(CallingCode::UNITED_KINGDOM, CallingCode::fromPhoneNumber('011441234567890'));
        $this->assertEquals(null, CallingCode::fromPhoneNumber('2'));
    }
}
