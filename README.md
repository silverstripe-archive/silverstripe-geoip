# GeoIP Module

## Maintainer Contact

Sean Harvey (Nickname: sharvey, halkyon)
<sean (at) silverstripe (dot) com>

## Requirements

The GeoIP Binary

#### FreeBSD

```
cd /usr/ports/net/GeoIP
make install clean
```

#### Debian/Ubuntu

```
apt-get install geoip-bin geoip-database
```

#### CentOS/RHEL

```
yum install GeoIP GeoIP-data
```

## Installation

```
composer require "silverstripe/geoip" "dev-master"
```

## Features 

This module provides the GeoIP class for resolving IP addresses to country codes.
