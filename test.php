<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use Cryptomkt\Exchange\Client as Client;
use Cryptomkt\Exchange\Configuration as Configuration;

$apiKey = '2749585/c4YcC0PP0m3ZCtN6';
$apiSecret = 'fIos7smYNcbacu8cGyERbId1w4X79sBIRugNGwPQbJ3dqT8uHqNIvSb4tOPA9ES';


// var_dump(Configuration::apiKey($apiKey, $apiSecret));

$configuration = Configuration::apiKey($apiKey, $apiSecret);
$test = Client::create($configuration);

var_dump($test);