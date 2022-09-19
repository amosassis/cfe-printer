<?php

ini_set("display_errors", 0);

require_once '../vendor/autoload.php';

use CfePrinter\Pdf\CfePdfGenerator;
use CfePrinter\Xml\XmlReader;

$xmlString = file_get_contents('example2.xml');

$xml = new XmlReader($xmlString);
$pdf = new CfePdfGenerator($xml, false);

$pdf->getPDF('test.pdf');
