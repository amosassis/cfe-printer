<?php

use CfePrinter\Xml\XmlReader;

class XmlReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testXmlReaderInstatiation()
    {
        $xml = new XmlReader;
        $this->assertInstanceOf(XmlReader::class, $xml);
    }
}