<?php
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
