<?php

use CfePrinter\Pdf\CfePdfGenerator;

class CfePdfGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testCfePdfGeneratorInstantiation()
    {
        $pdf = new CfePdfGenerator;
        $this->assertInstanceOf(CfePdfGenerator::class,$pdf);
    }
}