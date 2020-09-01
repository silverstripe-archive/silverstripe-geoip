<?php

use Sunnysideup\Geoip\Geoip;
use SilverStripe\Dev\SapphireTest;

class GeoipTest extends SapphireTest
{
    
    public function testSetDefaultCountry()
    {
        Geoip::set_default_country_code('DE');
        Geoip::set_enabled(false);
        
        $this->assertEquals('DE', Geoip::visitor_country());
        $this->assertEquals('DE', Geoip::get_default_country_code());
    }
}
