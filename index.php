<?php
require_once 'vendor/autoload.php';
use UAParser\Parser;

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

 if(isMobile()) {
    echo file_get_contents((file_exists('./mobile.html') ? './mobile.html' : './desktop.html'), true);
 } else {
    echo file_get_contents('./desktop.html', true);
 }