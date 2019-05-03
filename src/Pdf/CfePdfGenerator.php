<?php

namespace CfePrinter\Pdf;

use TCPDF as Pdf;
use CfePrinter\Xml\XmlReader;
use CfePrinter\Common\PaymentTypes;

class CfePdfGenerator
{

    private $pdf;  
    private $pageWidth;
    private $font;
    private $id;
    private $ide;
    private $emit;
    private $dest;
    private $det;
    private $total;
    private $payments;
    private $infoAdd;

    function __construct(XmlReader $xml, $pageWidth = 75, $font = 'helvetica')
    {  
        $this->id = $xml->getId();
        $this->ide = $xml->getIde();
        $this->emit = $xml->getEmit();
        $this->dest = $xml->getDest();
        $this->det = $xml->getDet();
        $this->total = $xml->getTotal();
        $this->payments = $xml->getPgto();    
        $this->infoAdd = $xml->getInfAdic();    

        $this->pageWidth = $pageWidth;
        $this->font = $font;
        $this->pdf = new Pdf();
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        $this->createPDF();
    }

    function createPage()
    {
        $this->pdf->SetFont($this->font, '', 12);
        $page_format = array(
            'MediaBox' => array('llx' => 0, 'lly' => 0, 'urx' => 1000, 'ury' => 80),
            'Dur' => 0,
            'Rotate' => 0,
            'PZ' => 0,
        );
        $this->pdf->AddPage('P', $page_format, false, false);
        $this->pdf->SetMargins(1, 1, 0, true);
        $this->pdf->SetY(2, true, true);
    }

    function setHeader()
    {                
        $this->pdf->SetFont($this->font, 'B', 11);
        $this->pdf->Cell($this->pageWidth, 5, $this->emit->xFant, 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 8);
        $this->pdf->Cell($this->pageWidth, 5, $this->emit->xNome, 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, "{$this->emit->enderEmit->xLgr}, {$this->emit->enderEmit->nro} {$this->emit->enderEmit->xCpl}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, "{$this->emit->enderEmit->xBairro} - {$this->emit->enderEmit->xMun}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 8.5);
        $this->pdf->Cell($this->pageWidth, 5, "CNPJ:{$this->emit->CNPJ} IE:{$this->emit->IE}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, 'B', 8);
        $this->pdf->MultiCell($this->pageWidth, 5, "EXTRATO Nº {$this->ide->nCFe} do CUPOM FISCAL ELETRÔNICO - SAT", 0, 'C', 0);
        $this->pdf->Ln();
    }

    function setProducts()
    {        
        $this->pdf->SetFont($this->font, '', 7);
        $this->pdf->Cell($this->pageWidth, 5, '#|COD|DESC|QTD|UN|VL UN R$|VL TR R$|VL ITEM R$');
        $this->setDividerLine();
        $index = 1;
        foreach ($this->det as $det) {
            $prod = $det->prod;
            $imposto = $det->imposto;
            $qtd = (is_integer($prod->qCom)) ? round($prod->qCom, 0) : round($prod->qCom, 3);
            $prodDescription = substr("{$prod->cProd} {$prod->xProd}", 0, 27);
            $item = str_pad($index, 3, '0', STR_PAD_LEFT);
            $this->pdf->Cell(50, 3.5, "{$item} {$prodDescription} {$qtd} {$prod->uCom} X {$prod->vUnCom}");
            $this->pdf->Cell(20, 3.5, number_format((float)$prod->vItem, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
            $index++;
        }
        $this->pdf->Ln();
    }

    function setTotals()
    {        
        $this->pdf->SetFont($this->font, 'B', 12);
        $this->pdf->Cell(45, 5, 'TOTAL R$');
        $this->pdf->Cell(25, 5, number_format((float)$this->total->vCFe, 2, ',', '.'), 0, 0, 'R');
        $this->pdf->Ln();
    }

    function setPayments()
    {        
        $this->pdf->SetFont($this->font, '', 8);
        foreach ($this->payments as $payment) {
            $this->pdf->Cell(45, 5, PaymentTypes::byCode($payment->MP->cMP));
            $this->pdf->Cell(25, 5, number_format((float)$payment->MP->vMP, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
        }
        if ($this->payments->vTroco > 0) {
            $this->pdf->SetFont($this->font, '', 10);
            $this->pdf->Cell(45, 5, 'Troco R$');
            $this->pdf->Cell(25, 5, number_format((float)$this->payments->vTroco, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
        }
    }

    function setContriberNotes()
    {       
        $this->pdf->SetFont($this->font, '', 8);
        $this->pdf->Cell($this->pageWidth, 5, 'OBSERVAÇÕES DO CONTRIBUINTE');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, $this->infoAdd->infCpl);
        $this->pdf->Ln();        
        $this->pdf->MultiCell(45, 5, 'Valor aproximado dos tributos deste cupom (Conforme Lei Fed. 12.741/2012)', 0, 'L', 0, 0);
        $this->pdf->MultiCell(25, 5, number_format((float)$this->total->vCFeLei12741, 2, ',', '.'), 0, 'R');
        $this->pdf->Ln();
    }

    function setCFeId()
    {
        $id = $this->id;
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 6.5);
        $formt = sprintf("%s %s %s %s %s %s %s %s %s %s %s %s", substr($id, 0, 4), substr($id, 4, 4), substr($id, 8, 4), substr($id, 12, 4), substr($id, 16, 4), substr($id, 18, 4), substr($id, 20, 4), substr($id, 24, 4), substr($id, 28, 4), substr($id, 32, 4), substr($id, 36, 4), substr($id, 40, 4));
        $this->pdf->Cell($this->pageWidth, 5, $formt, 0, 'C');
    }

    function setBarcode()
    {        
        $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255),
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );
        $this->pdf->Ln();
        $this->pdf->write1DBarcode(substr($this->id, 0, 20), 'C128A', 10, '', 50, 10, 0.4, $style, 'C');
        $this->pdf->Ln(10);
        $this->pdf->write1DBarcode(substr($this->id, 20, 20), 'C128A', 10, '', 50, 10, 0.4, $style, 'C');
        $this->pdf->Ln();
    }

    function setQRCode()
    {        
        $style = array(
            'border' => 5,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );        

        $qrCodeString = "{$this->id}|{$this->ide->dEmi}{$this->ide->hEmi}|{$this->total->vCFe}|{$this->getNumDoc()}|{$this->ide->assinaturaQRCODE}";

        $this->pdf->SetY($this->pdf->GetY() + 10);
        $this->pdf->write2DBarcode($qrCodeString, 'QRCODE,H', 2, $this->pdf->GetY(), 37, 37, $style, 'C');
    }

    function getNumDoc()
    {
        $cpf = $this->dest->CPF ? $this->dest->CPF : '';
        $cnpj = $this->dest->CNPJ ? $this->dest->CNPJ : '';
        return $cpf ? $cpf : $cnpj;
    }

    function setSATData()
    {        
        $yValue = $this->pdf->GetY();
        $xValue = 40;
        $this->pdf->Text($xValue, $yValue, 'Consumidor');
        $yValue += 3.5;
        $this->pdf->Text($xValue, $yValue, $this->dest->xNome);
        $numDoc = $this->getNumDoc();
        if ($numDoc) {
            $yValue += 3.5;
            $this->pdf->Text($xValue, $yValue, "Doc: {$numDoc}");
        }
        $yValue += 3.5;
        $this->pdf->Text($xValue, $yValue, "Nº Serie SAT: {$this->ide->nserieSAT}");
        $yValue += 3.5;
        $dtEmi = \DateTime::createFromFormat("Ymd", $this->ide->dEmi);
        $hEmi = \DateTime::createFromFormat("His", $this->ide->hEmi);
        $this->pdf->Text($xValue, $yValue, "{$dtEmi->format("d/m/Y")} {$hEmi->format("H:i:s")}");
        $this->pdf->SetY($this->pdf->GetY() + 6);
        $this->pdf->Cell(38, 5, '');
        $this->pdf->MultiCell(35, 5, 'Consulte o QRCode pelo aplicativo "De olho na nota", disponível na AppStore (Apple) e PlayStore (Android)', 0, 'C', 0);
    }


    function setDividerLine()
    {
        $this->pdf->Ln();
        $this->pdf->Line(2, $this->pdf->GetY(), $this->pageWidth, $this->pdf->GetY(), ['width' => 0.1, 'dash' => 3]);
    }

    function createPDF()
    {
        $this->createPage();
        $this->setHeader();
        $this->setProducts();
        $this->setTotals();
        $this->setPayments();
        $this->setContriberNotes();
        $this->setCFeId();
        $this->setBarcode();
        $this->setQRCode();
        $this->setSATData();
    }

    function getPDF($filename, $dest = 'I')
    {
        $this->pdf->Output($filename, $dest);
    }
}
