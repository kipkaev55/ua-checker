<?php
require_once 'vendor/autoload.php';
use UAParser\Parser;
use GeoIp2\Database\Reader;

define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR);
define('VENDORPATH', realpath(__DIR__.'/vendor/').DIRECTORY_SEPARATOR);

function downloadGeo()
{
    set_time_limit(0);
    //This input should be from somewhere else, hard-coded in this example
    $file_name = './vendor/geoip2/geoip2/maxmind-db/GeoLite2-City.mmdb.gz';

    //get GeoLite2 from HTTP
    file_put_contents($file_name, fopen('http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz', 'r'));

    // Raising this value may increase performance
    $buffer_size = 4096; // read 4kb at a time
    $out_file_name = str_replace('.gz', '', $file_name);

    // Open our files (in binary mode)
    $file = gzopen($file_name, 'rb');
    $out_file = fopen($out_file_name, 'wb');

    // Keep repeating until the end of the input file
    while (!gzeof($file)) {
        // Read buffer-size bytes
        // Both fwrite and gzread and binary-safe
        fwrite($out_file, gzread($file, $buffer_size));
    }

    // Files are done, close files
    fclose($out_file);
    gzclose($file);

    //remove file
    unlink($file_name);
}

function getGeo()
{
    if(!file_exists(VENDORPATH.'/geoip2/geoip2/maxmind-db/GeoLite2-City.mmdb')){
        downloadGeo();
    }
    $reader = new Reader(VENDORPATH.'/geoip2/geoip2/maxmind-db/GeoLite2-City.mmdb', array('ru'));
    $data = array();
    $data['ip'] = $_SERVER['REMOTE_ADDR'];
    // $data['ip'] = "54.242.105.109";
    try {
        $resp = $reader->city($data['ip']);
        $data['country'] = (($resp->country->isoCode != null) ? $resp->country->isoCode : "UN");
        $city = null;
        if($resp->city->name != null){
            $city = $resp->city->name;
        } elseif($resp->city->names['en'] != null) {
            $city = $resp->city->names['en'];
        } elseif($resp->mostSpecificSubdivision->name != null) {
            $city = $resp->mostSpecificSubdivision->name;
        } elseif($resp->mostSpecificSubdivision->names['en'] != null) {
            $city = $resp->mostSpecificSubdivision->names['en'];
        } else {
            $city = "Unknown";
        }
        $data['city'] = $city;
    } catch (GeoIp2\Exception\AddressNotFoundException $e) {
        if((ip2long($data['ip']) >= 167772160 && ip2long($data['ip']) <= 184549375)
            || (ip2long($data['ip']) >= 2886729728 && ip2long($data['ip']) <= 2887778303)
            || (ip2long($data['ip']) >= 3232235520 && ip2long($data['ip']) <= 3232301055)) { //networks classes A,B,C
            $data['country'] = 'LO';
            $data['city'] = 'Local Network';
        } elseif((ip2long($data['ip']) >= 2130706432 && ip2long($data['ip']) <= 2147483647)){
            $data['country'] = 'LO';
            $data['city'] = 'Loopback';
        } else {
            $data['country'] = 'UN';
            $data['city'] = 'Unknown';
        }
    }
    return $data;
}

function isMobile()
{
    $notMobile = array(
          'Other',
          'Spider',
          'WebTV',
          'Nintendo Wii',
          'Nintendo DS',
          'PlayStation 3',
          'PlayStation Portable'
      );
    $parser = Parser::create();
    $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);
    $isMobile = !in_array($result->device->family, $notMobile);
    return $isMobile;
}

$geo = getGeo();
$page = file_get_contents('./desktop.html', true);
if(isMobile()) {
    $page = file_get_contents((file_exists('./mobile.html') ? './mobile.html' : './desktop.html'), true);
}
$locale = file_get_contents('locale.json');
$jsonLocale = json_decode($locale);
foreach ($jsonLocale as $key => $value) {
    if(property_exists($jsonLocale->$key, $geo['country'])) {
        $arrValue = get_object_vars($jsonLocale->$key);
        $page = str_replace("{{%".$key."%}}", $arrValue[$geo['country']], $page);
    } else {
        $page = str_replace("{{%".$key."%}}", $jsonLocale->$key->RU, $page);
    }
}
echo $page;
