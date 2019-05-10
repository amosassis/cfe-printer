<?php

require_once '../vendor/autoload.php';

use CfePrinter\Pdf\CfePdfGenerator;
use CfePrinter\Xml\XmlReader;

$xmlString = file_get_contents('example.xml');
$xmlStringCanc = file_get_contents('example-canc.xml');

$xml = new XmlReader($xmlString);
$xmlCanc = new XmlReader($xmlStringCanc);

$pdf = new CfePdfGenerator($xml, false);
$pdf->setCancelledCoupom($xmlCanc);

$pdf->getPDF('test.pdf');
