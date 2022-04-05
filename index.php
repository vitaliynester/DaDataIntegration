<?php

require 'vendor/autoload.php';
require 'Parser.php';

$inputFileName = $argv[1];

$parser = new Parser($inputFileName);
$addresses = $parser->getAddresses();
$result = $parser->getGeoData($addresses);

$fp = fopen('results.json', 'w');
fwrite($fp, json_encode($result, JSON_UNESCAPED_UNICODE));
fclose($fp);
