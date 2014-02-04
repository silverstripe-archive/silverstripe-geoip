<?php

class GeoipTest extends SapphireTest {
	
	function testSetDefaultCountry() {
		Geoip::set_default_country_code('DE');
		Geoip::set_enabled(false);
		
		$this->assertEquals('DE', Geoip::visitor_country());
		$this->assertEquals('DE', Geoip::get_default_country_code());
		Geoip::set_enabled(true);
	}
	
	function testIP2CountryIPv4Lookup() {
		Geoip::set_enabled(true);
		$this->assertEquals('NZ', Geoip::ip2country('14.1.32.1', true));
	}
	
	function testIP2CountryIPv6Lookup() {
		Geoip::set_enabled(true);
		$this->assertEquals('CA', Geoip::ip2country('2001:410:1:2:3:4:5:6', true));
	}
}