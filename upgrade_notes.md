2020-09-01 08:40

# running php upgrade upgrade see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code upgrade /var/www/ss3/upgrades/ecommerce-root/geoip  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Writing changes for 2 files
Running upgrades on "/var/www/ss3/upgrades/ecommerce-root/geoip"
[2020-09-01 20:40:30] Applying RenameClasses to _config.php...
[2020-09-01 20:40:30] Applying ClassToTraitRule to _config.php...
[2020-09-01 20:40:30] Applying RenameClasses to GeoipTest.php...
[2020-09-01 20:40:30] Applying ClassToTraitRule to GeoipTest.php...
[2020-09-01 20:40:30] Applying RenameClasses to Geoip.php...
[2020-09-01 20:40:30] Applying ClassToTraitRule to Geoip.php...
modified:	tests/GeoipTest.php
@@ -1,4 +1,7 @@
 <?php
+
+use Sunnysideup\Geoip\Geoip;
+use SilverStripe\Dev\SapphireTest;

 class GeoipTest extends SapphireTest
 {

modified:	src/Geoip.php
@@ -2,7 +2,9 @@

 namespace Sunnysideup\Geoip;

-use Director;
+
+use SilverStripe\Control\Director;
+


 /**

Writing changes for 2 files
✔✔✔