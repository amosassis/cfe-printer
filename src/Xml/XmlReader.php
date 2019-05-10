<?php

namespace CfePrinter\Xml;

class XmlReader
{
    private $xml;

    function __construct($xml)
    {
        $this->xml = simplexml_load_string($xml);
    }

    function getIdCanc()
    {        
        return isset($this->xml->infCFe[0]->attributes()['chCanc']) ? substr($this->xml->infCFe[0]->attributes()['chCanc'],3) : '';
    }

    function getId()
    {
        return substr($this->xml->infCFe[0]->attributes()['Id'], 3);
    }

    function getIde()
    {
        return $this->xml->infCFe->ide;
    }

    function getEmit()
    {
        return $this->xml->infCFe->emit;
    }

    function getDest()
    {
        return $this->xml->infCFe->dest;
    }

    function getDet()
    {
        return $this->xml->infCFe->det;
    }

    function getTotal()
    {
        return $this->xml->infCFe->total;
    }

    function getPgto()
    {
        return $this->xml->infCFe->pgto;
    }

    function getInfAdic()
    {
        return $this->xml->infCFe->infAdic;
    }

    function getSignature()
    {
        return $this->xml->Signature;
    }

    function getXML()
    {
        return $this->xml;
    }
}
