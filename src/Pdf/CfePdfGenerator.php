<?php

namespace CfePrinter\Pdf;

class CfePdfGenerator{

    private $pdf;

    function __construct()
    {
        $this->pdf = new ZendPdf;
    }
}

