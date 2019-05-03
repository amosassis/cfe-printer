<?php

require_once '../vendor/autoload.php';

use CfePrinter\Pdf\CfePdfGenerator;
use CfePrinter\Xml\XmlReader;

$xmlString = file_get_contents('example.xml');

$xml = new XmlReader($xmlString);

$pdf = new CfePdfGenerator($xml);

$pdf->getPDF('test.pdf');
