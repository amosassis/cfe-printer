<?php

namespace CfePrinter\Tests;

class CfePdfGeneratorTests extends \PHPUnit_Framework_TestCase
{

    function testCfePdfGeneratorInstantiation()
    {
        $pdfPrinter = new \CfePrinter\CfePdfGenerator;
        $this->assertInstanceOf('CfePdfGenerator', $pdfPrinter);
    }
}