<?php
/**
 * Handles looking up the country that an IP address is from using the
 * MaxMind databases. This is based on the PEAR GeoIPLookup package, but with the 
 * following changes:
 * - Added IPv6 support
 * - Reduced functionality to country lookup only 
 *
 * +----------------------------------------------------------------------+
 * | PHP version 5                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (C) 2004 MaxMind LLC                                       |
 * +----------------------------------------------------------------------+
 * | This library is free software; you can redistribute it and/or        |
 * | modify it under the terms of the GNU Lesser General Public           |
 * | License as published by the Free Software Foundation; either         |
 * | version 2.1 of the License, or (at your option) any later version.   |
 * |                                                                      |
 * | This library is distributed in the hope that it will be useful,      |
 * | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
 * | Lesser General Public License for more details.                      |
 * |                                                                      |
 * | You should have received a copy of the GNU Lesser General Public     |
 * | License along with this library; if not, write to the Free Software  |
 * | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 |
 * | USA, or view it online at http://www.gnu.org/licenses/lgpl.txt.      |
 * +----------------------------------------------------------------------+
 * | Authors: Jim Winstead <jimw@apache.org> (original Maxmind version)   |
 * |          Hans Lellelid <hans@xmpl.org>                               |
 * |          Hans de Ruiter <hans <at> hdrlab.org.nz>                    |
 * |                                       (cut down GeoIPLookup version) |
 * +----------------------------------------------------------------------+
 *
 * @package  GeoIPLookup
 * @author   Jim Winstead <jimw@apache.org> (original Maxmind PHP API)
 * @author   Hans Lellelid <hans@xmpl.org>
 * @author   Hans de Ruiter <hans <at> hdrlab.org.nz> (cut down GeoIPLookup version)
 * @license  LGPL http://www.gnu.org/licenses/lgpl.txt
 * @link     https://github.com/hdrlab/silverstripe-geoip
 */

/**
 * GeoIPLookup class provides an API for performing geo-location country lookups 
 * based on IP address.
 *
 * To use this class you must have a [binary version] GeoIP database. There is
 * a free GeoIP country database which can be obtained from Maxmind:
 * {@link http://www.maxmind.com/app/geoip_country}
 *
 *
 * <b>SIMPLE USE</b>
 *
 *
 * Create an instance:
 *
 * <code>
 * $geoip = GeoIPLookup::getInstance('/path/to/geoipdb.dat', GeoIPLookup::SHARED_MEMORY);
 * </code>
 *
 * Depending on which database you are using (free, or one of paid versions)
 * you must use appropriate lookup method:
 *
 * <code>
 * // for free country db:
 * $country_name = $geoip->lookupCountryName($_SERVER['REMOTE_ADDR']);
 * $country_code = $geoip->lookupCountryCode($_SERVER['REMOTE_ADDR']);
 *
 * // for [non-free] region db:
 * list($ctry_code, $region) = $geoip->lookupRegion($_SERVER['REMOTE_ADDR']);
 *
 * // for [non-free] city db:
 * $location = $geoip->lookupLocation($_SERVER['REMOTE_ADDR']);
 * print "city: " . $location->city . ", " . $location->region;
 * print "lat: " . $location->latitude . ", long: " . $location->longitude;
 *
 * // for organization or ISP db:
 * $org_or_isp_name = $geoip->lookupOrg($_SERVER['REMOTE_ADDR']);
 * </code>
 *
 *
 * <b>MULTIPLE INSTANCES</b>
 *
 *
 * You can have several instances of this class, one for each database file
 * you are using.  You should use the static getInstance() singleton method
 * to save on overhead of setting up database segments.  Note that only one
 * instance is stored per filename, and any flags will be ignored if an
 * instance already exists for the specifiedfilename.
 *
 * <b>Special note on using SHARED_MEMORY flag</b>
 *
 * If you are using SHARED_MEMORY (shmop) you can only use SHARED_MEMORY for
 * one (1) instance  (i.e. for one database). Any subsequent attempts to
 * instantiate using SHARED_MEMORY will read the same shared memory block
 * already initialized, and therefore will cause problems since the expected
 * database format won't match the database in the shared memory block.
 *
 * Note that there is no easy way to flag "nice errors" to prevent attempts
 * to create new instances using SHARED_MEMORY flag and it is also not posible
 * (in a safe way) to allow new instances to overwrite the shared memory block.
 *
 * In short, is you are using multiple databses, use the SHARED_MEMORY flag
 * with care.
 *
 *
 * <b>LOOKUPS ON HOSTNAMES</b>
 *
 *
 * Note that this PHP API does NOT support lookups on hostnames.  This is so
 * that the public API can be kept simple and so that the lookup functions
 * don't need to try name lookups if IP lookup fails (which would be the only
 * way to keep the API simple and support name-based lookups).
 *
 * If you do not know the IP address, you can convert an name to IP very
 * simply using PHP native functions or other libraries:
 *
 * <code>
 *     $geoip->lookupCountryName(gethostbyname('www.sunset.se'));
 * </code>
 *
 * Or, if you don't know whether an address is a name or ip address, use
 * application-level logic:
 *
 * <code>
 * if (ip2long($ip_or_name) === false) {
 *   $ip = gethostbyname($ip_or_name);
 * } else {
 *   $ip = $ip_or_name;
 * }
 * $ctry = $geoip->lookupCountryName($ip);
 * </code>
 *
 * @package  GeoIPLookup
 * @author   Jim Winstead <jimw@apache.org> (original Maxmind PHP API)
 * @author   Hans Lellelid <hans@xmpl.org>
 * @author   Hans de Ruiter <hans <at> hdrlab.org.nz> (cut down GeoIPLookup version)
 * @license  LGPL http://www.gnu.org/licenses/lgpl.txt
 * @link     https://github.com/hdrlab/silverstripe-geoip
 */
class GeoIPLookup
{
    /**
     * Exception error code used for invalid IP address.
     */
    const ERR_INVALID_IP =  218624992; // crc32('GeoIPLookup::ERR_INVALID_IP')

    /**
     * Exception error code when there is a DB-format-related error.
     */
    const ERR_DB_FORMAT = 866184008; // crc32('GeoIPLookup::ERR_DB_FORMAT')

    public static $COUNTRY_CODES = array(
      "", "AP", "EU", "AD", "AE", "AF", "AG", "AI", "AL", "AM", "AN", "AO", "AQ",
      "AR", "AS", "AT", "AU", "AW", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH",
      "BI", "BJ", "BM", "BN", "BO", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA",
      "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CU",
      "CV", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG",
      "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "FX", "GA", "GB",
      "GD", "GE", "GF", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT",
      "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IN",
      "IO", "IQ", "IR", "IS", "IT", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM",
      "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS",
      "LT", "LU", "LV", "LY", "MA", "MC", "MD", "MG", "MH", "MK", "ML", "MM", "MN",
      "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA",
      "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA",
      "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY",
      "QA", "RE", "RO", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI",
      "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "ST", "SV", "SY", "SZ", "TC", "TD",
      "TF", "TG", "TH", "TJ", "TK", "TM", "TN", "TO", "TL", "TR", "TT", "TV", "TW",
      "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN",
      "VU", "WF", "WS", "YE", "YT", "RS", "ZA", "ZM", "ME", "ZW", "A1", "A2", "O1",
      "AX", "GG", "IM", "JE", "BL", "MF"
        );

    public static $COUNTRY_CODES3 = array(
    "","AP","EU","AND","ARE","AFG","ATG","AIA","ALB","ARM","ANT","AGO","AQ","ARG",
    "ASM","AUT","AUS","ABW","AZE","BIH","BRB","BGD","BEL","BFA","BGR","BHR","BDI",
    "BEN","BMU","BRN","BOL","BRA","BHS","BTN","BV","BWA","BLR","BLZ","CAN","CC",
    "COD","CAF","COG","CHE","CIV","COK","CHL","CMR","CHN","COL","CRI","CUB","CPV",
    "CX","CYP","CZE","DEU","DJI","DNK","DMA","DOM","DZA","ECU","EST","EGY","ESH",
    "ERI","ESP","ETH","FIN","FJI","FLK","FSM","FRO","FRA","FX","GAB","GBR","GRD",
    "GEO","GUF","GHA","GIB","GRL","GMB","GIN","GLP","GNQ","GRC","GS","GTM","GUM",
    "GNB","GUY","HKG","HM","HND","HRV","HTI","HUN","IDN","IRL","ISR","IND","IO",
    "IRQ","IRN","ISL","ITA","JAM","JOR","JPN","KEN","KGZ","KHM","KIR","COM","KNA",
    "PRK","KOR","KWT","CYM","KAZ","LAO","LBN","LCA","LIE","LKA","LBR","LSO","LTU",
    "LUX","LVA","LBY","MAR","MCO","MDA","MDG","MHL","MKD","MLI","MMR","MNG","MAC",
    "MNP","MTQ","MRT","MSR","MLT","MUS","MDV","MWI","MEX","MYS","MOZ","NAM","NCL",
    "NER","NFK","NGA","NIC","NLD","NOR","NPL","NRU","NIU","NZL","OMN","PAN","PER",
    "PYF","PNG","PHL","PAK","POL","SPM","PCN","PRI","PSE","PRT","PLW","PRY","QAT",
    "REU","ROU","RUS","RWA","SAU","SLB","SYC","SDN","SWE","SGP","SHN","SVN","SJM",
    "SVK","SLE","SMR","SEN","SOM","SUR","STP","SLV","SYR","SWZ","TCA","TCD","TF",
    "TGO","THA","TJK","TKL","TLS","TKM","TUN","TON","TUR","TTO","TUV","TWN","TZA",
    "UKR","UGA","UM","USA","URY","UZB","VAT","VCT","VEN","VGB","VIR","VNM","VUT",
    "WLF","WSM","YEM","YT","SRB","ZAF","ZMB","MNE","ZWE","A1","A2","O1",
    "ALA","GGY","IMN","JEY","BLM","MAF"
        );

    public static $COUNTRY_NAMES = array(
        "", "Asia/Pacific Region", "Europe", "Andorra", "United Arab Emirates",
        "Afghanistan", "Antigua and Barbuda", "Anguilla", "Albania", "Armenia",
        "Netherlands Antilles", "Angola", "Antarctica", "Argentina", "American Samoa",
        "Austria", "Australia", "Aruba", "Azerbaijan", "Bosnia and Herzegovina",
        "Barbados", "Bangladesh", "Belgium", "Burkina Faso", "Bulgaria", "Bahrain",
        "Burundi", "Benin", "Bermuda", "Brunei Darussalam", "Bolivia", "Brazil",
        "Bahamas", "Bhutan", "Bouvet Island", "Botswana", "Belarus", "Belize",
        "Canada", "Cocos (Keeling) Islands", "Congo, The Democratic Republic of the",
        "Central African Republic", "Congo", "Switzerland", "Cote D'Ivoire", "Cook Islands",
        "Chile", "Cameroon", "China", "Colombia", "Costa Rica", "Cuba", "Cape Verde",
        "Christmas Island", "Cyprus", "Czech Republic", "Germany", "Djibouti",
        "Denmark", "Dominica", "Dominican Republic", "Algeria", "Ecuador", "Estonia",
        "Egypt", "Western Sahara", "Eritrea", "Spain", "Ethiopia", "Finland", "Fiji",
        "Falkland Islands (Malvinas)", "Micronesia, Federated States of", "Faroe Islands",
        "France", "France, Metropolitan", "Gabon", "United Kingdom",
        "Grenada", "Georgia", "French Guiana", "Ghana", "Gibraltar", "Greenland",
        "Gambia", "Guinea", "Guadeloupe", "Equatorial Guinea", "Greece", "South Georgia and the South Sandwich Islands",
        "Guatemala", "Guam", "Guinea-Bissau",
        "Guyana", "Hong Kong", "Heard Island and McDonald Islands", "Honduras",
        "Croatia", "Haiti", "Hungary", "Indonesia", "Ireland", "Israel", "India",
        "British Indian Ocean Territory", "Iraq", "Iran, Islamic Republic of",
        "Iceland", "Italy", "Jamaica", "Jordan", "Japan", "Kenya", "Kyrgyzstan",
        "Cambodia", "Kiribati", "Comoros", "Saint Kitts and Nevis", "Korea, Democratic People's Republic of",
        "Korea, Republic of", "Kuwait", "Cayman Islands",
        "Kazakstan", "Lao People's Democratic Republic", "Lebanon", "Saint Lucia",
        "Liechtenstein", "Sri Lanka", "Liberia", "Lesotho", "Lithuania", "Luxembourg",
        "Latvia", "Libyan Arab Jamahiriya", "Morocco", "Monaco", "Moldova, Republic of",
        "Madagascar", "Marshall Islands", "Macedonia",
        "Mali", "Myanmar", "Mongolia", "Macau", "Northern Mariana Islands",
        "Martinique", "Mauritania", "Montserrat", "Malta", "Mauritius", "Maldives",
        "Malawi", "Mexico", "Malaysia", "Mozambique", "Namibia", "New Caledonia",
        "Niger", "Norfolk Island", "Nigeria", "Nicaragua", "Netherlands", "Norway",
        "Nepal", "Nauru", "Niue", "New Zealand", "Oman", "Panama", "Peru", "French Polynesia",
        "Papua New Guinea", "Philippines", "Pakistan", "Poland", "Saint Pierre and Miquelon",
        "Pitcairn Islands", "Puerto Rico", "Palestinian Territory",
        "Portugal", "Palau", "Paraguay", "Qatar", "Reunion", "Romania",
        "Russian Federation", "Rwanda", "Saudi Arabia", "Solomon Islands",
        "Seychelles", "Sudan", "Sweden", "Singapore", "Saint Helena", "Slovenia",
        "Svalbard and Jan Mayen", "Slovakia", "Sierra Leone", "San Marino", "Senegal",
        "Somalia", "Suriname", "Sao Tome and Principe", "El Salvador", "Syrian Arab Republic",
        "Swaziland", "Turks and Caicos Islands", "Chad", "French Southern Territories",
        "Togo", "Thailand", "Tajikistan", "Tokelau", "Turkmenistan",
        "Tunisia", "Tonga", "Timor-Leste", "Turkey", "Trinidad and Tobago", "Tuvalu",
        "Taiwan", "Tanzania, United Republic of", "Ukraine",
        "Uganda", "United States Minor Outlying Islands", "United States", "Uruguay",
        "Uzbekistan", "Holy See (Vatican City State)", "Saint Vincent and the Grenadines",
        "Venezuela", "Virgin Islands, British", "Virgin Islands, U.S.",
        "Vietnam", "Vanuatu", "Wallis and Futuna", "Samoa", "Yemen", "Mayotte",
        "Serbia", "South Africa", "Zambia", "Montenegro", "Zimbabwe",
        "Anonymous Proxy","Satellite Provider","Other",
        "Aland Islands","Guernsey","Isle of Man","Jersey","Saint Barthelemy","Saint Martin"
        );

    // storage / caching flags
    const STANDARD = 0;
    const MEMORY_CACHE = 1;
    const SHARED_MEMORY = 2;

    // Database structure constants
    const COUNTRY_BEGIN = 16776960;
    const STATE_BEGIN_REV0 = 16700000;
    const STATE_BEGIN_REV1 = 16000000;

    const STRUCTURE_INFO_MAX_SIZE = 20;
    const DATABASE_INFO_MAX_SIZE = 100;
    const COUNTRY_EDITION = 106;
    const COUNTRY_EDITION_V6 = 12;
    const REGION_EDITION_REV0 = 112;
    const REGION_EDITION_REV1 = 3;
    const CITY_EDITION_REV0 = 111;
    const CITY_EDITION_REV1 = 2;
    const ORG_EDITION = 110;
    const SEGMENT_RECORD_LENGTH = 3;
    const STANDARD_RECORD_LENGTH = 3;
    const ORG_RECORD_LENGTH = 4;
    const MAX_RECORD_LENGTH = 4;
    const MAX_ORG_RECORD_LENGTH = 300;
    const FULL_RECORD_LENGTH = 50;

    const US_OFFSET = 1;
    const CANADA_OFFSET = 677;
    const WORLD_OFFSET = 1353;
    const FIPS_RANGE = 360;

    // SHMOP memory address
    const SHM_KEY = 0x4f415401;

    /**
     * @var int
     */
    private $flags = 0;

    /**
     * @var resource
     */
    private $filehandle;

    /**
     * @var string
     */
    private $memoryBuffer;

    /**
     * @var int
     */
    private $databaseType;

    /**
     * @var int
     */
    private $databaseSegments;

    /**
     * @var int
     */
    private $recordLength;

    /**
     * The memory addr "id" for use with SHMOP.
     * @var int
     */
    private $shmid;

    /**
     * Support for singleton pattern.
     * @var array
     */
    private static $instances = array();

    /**
     * Construct a GeoIPLookup instance.
     * You should use the getInstance() method if you plan to use multiple databases or
     * the same database from several different places in your script.
     *
     * @param string $filename Path to binary geoip database.
     * @param int    $flags    Flags
     *
     * @see getInstance()
     */
    public function __construct($filename = null, $flags = null)
    {
        if ($filename !== null) {
            $this->open($filename, $flags);
        }
        // store the instance, so that it will be returned by a call to
        // getInstance() (with the same db filename).
        self::$instances[$filename] = $this;
    }

    /**
     * Calls the close() function to free any resources.
     * @see close()
     *
     * COMMENTED OUT TO ADDRESS BUG IN PHP 5.0.4, 5.0.5dev.  THIS RESOURCE
     * SHOULD AUTOMATICALLY BE FREED AT SCRIPT CLOSE, SO A DESTRUCTOR
     * IS A GOOD IDEA BUT NOT NECESSARILY A NECESSITY.
    public function __destruct()
    {
        $this->close();
    }
    */

    /**
     * Singleton method, use this to get an instance and avoid re-parsing the db.
     *
     * Unique instances are instantiated based on the filename of the db. The flags
     * are ignored -- in that requests to for instance with same filename but different
     * flags will return the already-instantiated instance.  For example:
     * <code>
     * // create new instance with memory_cache enabled
     * $geoip = GeoIPLookup::getInstance('C:\mydb.dat', GeoIPLookup::MEMORY_CACHE);
     * ....
     *
     * // later in code, request instance with no flags specified.
     * $geoip = GeoIPLookup::getInstance('C:\mydb.dat');
     *
     * // Normally this means no MEMORY_CACHE but since an instance
     * // with memory cache enabled has already been created for 'C:\mydb.dat', the
     * // existing instance (with memory cache) will be returned.
     * </code>
     *
     * NOTE: You can only use SHARED_MEMORY flag for one instance!  Any subsquent instances
     * that attempt to use the SHARED_MEMORY will use the *same* shared memory, which will break
     * your script.
     *
     * @param string $filename Filename
     * @param int    $flags    Flags that control class behavior.
     *          + GeoIPLookup::SHARED_MEMORY
     *             Use SHMOP to share a db among multiple PHP instances.
     *             NOTE: ONLY ONE GEOIP INSTANCE CAN USE SHARED MEMORY!!!
     *          + GeoIPLookup::MEMORY_CACHE
     *             Store the full contents of the database in memory for current script.
     *             This is useful if you access the database several times in a script.
     *          + GeoIPLookup::STANDARD
     *             [default] standard no-cache version.
     *
     * @return GeoIPLookup
     */
    public static function getInstance($filename = null, $flags = null)
    {
        if (!isset(self::$instances[$filename])) {
            self::$instances[$filename] = new GeoIPLookup($filename, $flags);
        }
        return self::$instances[$filename];
    }

    /**
     * Opens geoip database at filename and with specified flags.
     *
     * @param string $filename File to open
     * @param int    $flags    Flags
     *
     * @return void
     *
     * @throws Exception if unable to open specified file or shared memory.
     */
    public function open($filename, $flags = null)
    {
        if ($flags !== null) {
            $this->flags = $flags;
        }
        if ($this->flags & self::SHARED_MEMORY) {
            $this->shmid = @shmop_open(self::SHM_KEY, "a", 0, 0);
            if ($this->shmid === false) {
                $this->loadSharedMemory($filename);
                $this->shmid = @shmop_open(self::SHM_KEY, "a", 0, 0);
                if ($this->shmid === false) { // should never be false as loadSharedMemory() will throw Exc if cannot create
                    throw new Exception("Unable to open shared memory at key: " . dechex(self::SHM_KEY));
                }
            }
        } else {
            $this->filehandle = fopen($filename, "rb");
            if (!$this->filehandle) {
                throw new Exception("Unable to open file: $filename");
            }
            if ($this->flags & self::MEMORY_CACHE) {
                $s_array = fstat($this->filehandle);
                $this->memoryBuffer = fread($this->filehandle, $s_array['size']);
            }
        }
        $this->setupSegments();
    }

    /**
     * Loads the database file into shared memory.
     *
     * @param string $filename Path to database file to read into shared memory.
     *
     * @return void
     *
     * @throws Exception     - if unable to read the db file.
     */
    protected function loadSharedMemory($filename)
    {
        $fp = fopen($filename, "rb");
        if (!$fp) {
            throw new Exception("Unable to open file: $filename");
        }
        $s_array = fstat($fp);
        $size = $s_array['size'];

        if ($shmid = @shmop_open(self::SHM_KEY, "w", 0, 0)) {
            shmop_delete($shmid);
            shmop_close($shmid);
        }

        if ($shmid = @shmop_open(self::SHM_KEY, "c", 0644, $size)) {
            $offset = 0;
            while ($offset < $size) {
                $buf = fread($fp, 524288);
                shmop_write($shmid, $buf, $offset);
                $offset += 524288;
            }
            shmop_close($shmid);
        }

        fclose($fp);
    }

    /**
     * Parses the database file to determine what kind of database is being used and setup
     * segment sizes and start points that will be used by the seek*() methods later.
     *
     * @return void
     */
    protected function setupSegments()
    {

        $this->databaseType = self::COUNTRY_EDITION;
        $this->recordLength = self::STANDARD_RECORD_LENGTH;

        if ($this->flags & self::SHARED_MEMORY) {

            $offset = shmop_size($this->shmid) - 3;
            for ($i = 0; $i < self::STRUCTURE_INFO_MAX_SIZE; $i++) {
                $delim = shmop_read($this->shmid, $offset, 3);
                $offset += 3;
                if ($delim == (chr(255).chr(255).chr(255))) {
                    $this->databaseType = ord(shmop_read($this->shmid, $offset, 1));
                    $offset++;
                    if ($this->databaseType === self::REGION_EDITION_REV0) {
                        $this->databaseSegments = self::STATE_BEGIN_REV0;
                    } elseif ($this->databaseType === self::REGION_EDITION_REV1) {
                        $this->databaseSegments = self::STATE_BEGIN_REV1;
                    } elseif (($this->databaseType === self::CITY_EDITION_REV0)
                                || ($this->databaseType === self::CITY_EDITION_REV1)
                                || ($this->databaseType === self::ORG_EDITION)) {
                        $this->databaseSegments = 0;
                        $buf = shmop_read($this->shmid, $offset, self::SEGMENT_RECORD_LENGTH);
                        for ($j = 0; $j < self::SEGMENT_RECORD_LENGTH; $j++) {
                            $this->databaseSegments += (ord($buf[$j]) << ($j * 8));
                        }
                        if ($this->databaseType === self::ORG_EDITION) {
                            $this->recordLength = self::ORG_RECORD_LENGTH;
                        }
                    }
                    break;
                } else {
                    $offset -= 4;
                }
            }
            if ($this->databaseType == self::COUNTRY_EDITION ||
            	$this->databaseType == self::COUNTRY_EDITION_V6) {
                $this->databaseSegments = self::COUNTRY_BEGIN;
            }

        } else {

            $filepos = ftell($this->filehandle);
            fseek($this->filehandle, -3, SEEK_END);
            for ($i = 0; $i < self::STRUCTURE_INFO_MAX_SIZE; $i++) {
                $delim = fread($this->filehandle, 3);
                if ($delim == (chr(255).chr(255).chr(255))) {
                    $this->databaseType = ord(fread($this->filehandle, 1));
                    if ($this->databaseType === self::REGION_EDITION_REV0) {
                        $this->databaseSegments = self::STATE_BEGIN_REV0;
                    } elseif ($this->databaseType === self::REGION_EDITION_REV1) {
                        $this->databaseSegments = self::STATE_BEGIN_REV1;
                    } elseif ($this->databaseType === self::CITY_EDITION_REV0
                                || $this->databaseType === self::CITY_EDITION_REV1
                                || $this->databaseType === self::ORG_EDITION) {
                        $this->databaseSegments = 0;
                        $buf = fread($this->filehandle, self::SEGMENT_RECORD_LENGTH);
                        for ($j = 0; $j < self::SEGMENT_RECORD_LENGTH; $j++) {
                            $this->databaseSegments += (ord($buf[$j]) << ($j * 8));
                        }
                        if ($this->databaseType === self::ORG_EDITION) {
                            $this->recordLength = self::ORG_RECORD_LENGTH;
                        }
                    }
                    break;
                } else {
                    fseek($this->filehandle, -4, SEEK_CUR);
                }
            }
            if ($this->databaseType === self::COUNTRY_EDITION ||
            	$this->databaseType === self::COUNTRY_EDITION_V6) {
                $this->databaseSegments = self::COUNTRY_BEGIN;
            }
            fseek($this->filehandle, $filepos, SEEK_SET);

        }
    }

    /**
     * Closes the geoip database.
     *
     * @return int Status of close command.
     */
    public function close()
    {
        if ($this->flags & self::SHARED_MEMORY) {
            return shmop_close($this->shmid);
        } else {
            // right now even if file was cached in RAM the file was not closed
            // so it's safe to expect no error w/ fclose()
            return fclose($this->filehandle);
        }
    }

    /**
     * Get the country index.
     *
     * This method is called by the lookupCountryCode() and lookupCountryName()
     * methods.  It lookups up the index ('id') for the country which is the key
     * for the code and name.
     *
     * @param string $addr IP address (hostname not allowed)
     *
     * @throws Exception  - if IP address is invalid.
     *                         - if database type is incorrect
     *
     * @return string ID for the country
     */
    protected function lookupCountryId($addr)
    {
    	if ($this->databaseType === self::COUNTRY_EDITION)
    	{
			$ipnum = ip2long($addr);
			if ($ipnum === false) {
				throw new Exception("Invalid IP address: " . var_export($addr, true), self::ERR_INVALID_IP);
			}
			
			return $this->seekCountry($ipnum) - self::COUNTRY_BEGIN;
		}
		else if ($this->databaseType === self::COUNTRY_EDITION_V6)
		{
			$ipnum = inet_pton($addr);
			return $this->seekCountryIPv6($ipnum) - self::COUNTRY_BEGIN;
        }
        else
        {
            throw new Exception("Invalid database type; lookupCountry*() methods expect Country database." . $this->databaseType);
        }
    }

    /**
     * Returns 2-letter country code (e.g. 'CA') for specified IP address.
     * Use this method if you have a Country database.
     *
     * @param string $addr IP address (hostname not allowed).
     *
     * @return string 2-letter country code
     *
     * @throws Exception (see lookupCountryId())
     * @see lookupCountryId()
     */
    public function lookupCountryCode($addr)
    {
        return self::$COUNTRY_CODES[$this->lookupCountryId($addr)];
    }

    /**
     * Returns full country name for specified IP address.
     * Use this method if you have a Country database.
     *
     * @param string $addr IP address (hostname not allowed).
     *
     * @return string Country name
     * @throws Exception (see lookupCountryId())
     * @see lookupCountryId()
     */
    public function lookupCountryName($addr)
    {
        return self::$COUNTRY_NAMES[$this->lookupCountryId($addr)];
    }

    /**
     * Using the record length and appropriate start points, seek to the country that corresponds
     * to the converted IPv4 address integer.
     *
     * @param int $ipnum Result of ip2long() conversion.
     *
     * @return int Offset of start of record.
     * @throws Exception - if fseek() fails on the file or no results after traversing the database (indicating corrupt db).
     */
    protected function seekCountry($ipnum)
    {
        $offset = 0;
        for ($depth = 31; $depth >= 0; --$depth) {
            if ($this->flags & self::MEMORY_CACHE) {
                  $buf = substr($this->memoryBuffer, 2 * $this->recordLength * $offset, 2 * $this->recordLength);
            } elseif ($this->flags & self::SHARED_MEMORY) {
                $buf = shmop_read($this->shmid, 2 * $this->recordLength * $offset, 2 * $this->recordLength);
            } else {
                if (fseek($this->filehandle, 2 * $this->recordLength * $offset, SEEK_SET) !== 0) {
                    throw new Exception("fseek failed");
                }
                $buf = fread($this->filehandle, 2 * $this->recordLength);
            }
            $x = array(0,0);
            for ($i = 0; $i < 2; ++$i) {
                for ($j = 0; $j < $this->recordLength; ++$j) {
                    $x[$i] += ord($buf[$this->recordLength * $i + $j]) << ($j * 8);
                }
            }
            if ($ipnum & (1 << $depth)) {
                if ($x[1] >= $this->databaseSegments) {
                    return $x[1];
                }
                $offset = $x[1];
            } else {
                if ($x[0] >= $this->databaseSegments) {
                    return $x[0];
                }
                $offset = $x[0];
            }
        }
        throw new Exception("Error traversing database - perhaps it is corrupt?");
    }
    
    /**
     * Using the record length and appropriate start points, seek to the country that corresponds
     * to the converted IPv6address integer.
     *
     * @param int $ipnum Result of inet_pton() conversion.
     *
     * @return int Offset of start of record.
     * @throws Exception - if fseek() fails on the file or no results after traversing the database (indicating corrupt db).
     */
    protected function seekCountryIPv6($ipnum)
    {
    	$v6vec = array_merge(unpack( "C16", $ipnum));
    	 
        $offset = 0;
        for ($depth = 127; $depth >= 0; --$depth) {
            if ($this->flags & self::MEMORY_CACHE) {
                  $buf = substr($this->memoryBuffer, 2 * $this->recordLength * $offset, 2 * $this->recordLength);
            } elseif ($this->flags & self::SHARED_MEMORY) {
                $buf = shmop_read($this->shmid, 2 * $this->recordLength * $offset, 2 * $this->recordLength);
            } else {
                if (fseek($this->filehandle, 2 * $this->recordLength * $offset, SEEK_SET) !== 0) {
                    throw new Exception("fseek failed");
                }
                $buf = fread($this->filehandle, 2 * $this->recordLength);
            }
            $x = array(0,0);
            for ($i = 0; $i < 2; ++$i) {
                for ($j = 0; $j < $this->recordLength; ++$j) {
                    $x[$i] += ord($buf[$this->recordLength * $i + $j]) << ($j * 8);
                }
            }
			$bnum = 127 - $depth;
			$idx = $bnum >> 3;
			$b_mask = 1 << ( $bnum & 7 ^ 7 );
			if (($v6vec[$idx] & $b_mask) > 0) {
				if ($x[1] >= $this->databaseSegments) {
					return $x[1];
				}
				$offset = $x[1];
			} else {
				if ($x[0] >= $this->databaseSegments) {
					return $x[0];
				}
				$offset = $x[0];
			}
        }
        throw new Exception("Error traversing database - perhaps it is corrupt?");
    }
}

