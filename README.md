GeoIP Module
============

Handles looking up an IP address' country. By default this is done by executing
the geoiplookup command. However, it is also possible to use IP database tables
from MaxMind (http://www.maxmind.com/app/geoip_country) when this option is 
unavailable.

Maintainer Contact
------------------
Sean Harvey (Nickname: sharvey, halkyon)
<sean (at) silverstripe (dot) com>


Installation Instructions
-----------------------------------------------
- Find out how to add modules to SS and add module as per usual.
- If your server has geoiplookup, and the exec() function hasn't been disabled,
  then this is all that you need to do. Otherwise, you will have to download and
  setup the MaxMind IP country databases
  
Installing and Using MaxMind's Database Files:
- Download the databases (both IPv4 and IPv6) from MaxMind. They offer both a 
  commercial version (http://www.maxmind.com/en/country) and a free version 
  (http://www.maxmind.com/en/geolite)
- Unpack these databases, and place them somewhere on the server within the
  Silverstripe directory structure
- Add the following line to your mysite/_config.php:
  GeoIP::setGeoIPDatabases('path/to/GeoIP.dat', 'path/to/GeoIPv6.dat');
  You will have to replace "path/to" with the path to the two database files 
  relative to the Silverstripe base directory


