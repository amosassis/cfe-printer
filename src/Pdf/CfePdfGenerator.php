<?php

namespace CfePrinter\Pdf;

use TCPDF as Pdf;
use CfePrinter\Xml\XmlReader;
use CfePrinter\Common\PaymentTypes;

class CfePdfGenerator
{

    private $pdf;
    private $xml;
    private $pageWidth;
    private $font;




    function __construct(XmlReader $xml, $pageWidth = 75, $font = 'helvetica')
    {
        $this->pageWidth = $pageWidth;
        $this->font = $font;
        $this->pdf = new Pdf();
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->xml = $xml;
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
        $ide = $this->xml->getIde();
        $emit = $this->xml->getEmit();
        $this->pdf->SetFont($this->font, 'B', 11);
        $this->pdf->Cell($this->pageWidth, 5, $emit->xFant, 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 8);
        $this->pdf->Cell($this->pageWidth, 5, $emit->xNome, 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, "{$emit->enderEmit->xLgr}, {$emit->enderEmit->nro} {$emit->enderEmit->xCpl}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, "{$emit->enderEmit->xBairro} - {$emit->enderEmit->xMun}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 8.5);
        $this->pdf->Cell($this->pageWidth, 5, "CNPJ:{$emit->CNPJ} IE:{$emit->IE}", 0, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, 'B', 8);
        $this->pdf->MultiCell($this->pageWidth, 5, "EXTRATO Nº {$ide->nCFe} do CUPOM FISCAL ELETRÔNICO - SAT", 0, 'C', 0);
        $this->pdf->Ln();
    }

    function setProducts()
    {
        $products = $this->xml->getDet();
        $this->pdf->SetFont($this->font, '', 7.8);
        $this->pdf->Cell($this->pageWidth, 5, '#|COD|DESC|QTD|UN|VL UN R$|VL TR R$|VL ITEM R$');
        $this->setDividerLine();
        $index = 1;
        foreach ($products as $det) {
            $prod = $det->prod;
            $imposto = $det->imposto;
            $qtd = (is_integer($prod->qCom)) ? round($prod->qCom, 0) : round($prod->qCom, 3);
            $prodDescription = substr("{$prod->cProd} {$prod->xProd}", 0, 27);
            $item = str_pad($index, 3, '0', STR_PAD_LEFT);
            $this->pdf->Cell(55, 3.5, "{$item} {$prodDescription} {$qtd} {$prod->uCom} X {$prod->vUnCom}");
            $this->pdf->Cell(23, 3.5, number_format((float)$prod->vItem, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
            $index++;
        }
        $this->pdf->Ln();
    }

    function setTotals()
    {
        $total = $this->xml->getTotal();
        $this->pdf->SetFont($this->font, 'B', 12);
        $this->pdf->Cell(50, 5, 'TOTAL R$');
        $this->pdf->Cell(28, 5, number_format((float)$total->vCFe, 2, ',', '.'), 0, 0, 'R');
        $this->pdf->Ln();
    }

    function setPayments()
    {
        $payments = $this->xml->getPgto();
        $this->pdf->SetFont($this->font, '', 8);
        foreach ($payments as $payment) {
            $this->pdf->Cell(50, 5, PaymentTypes::byCode($payment->MP->cMP));
            $this->pdf->Cell(28, 5, number_format((float)$payment->MP->vMP, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
        }
        if ($payments->vTroco > 0) {
            $this->pdf->SetFont($this->font, '', 10);
            $this->pdf->Cell(50, 5, 'Troco R$');
            $this->pdf->Cell(28, 5, number_format((float)$payments->vTroco, 2, ',', '.'), 0, 0, 'R');
            $this->pdf->Ln();
        }
    }

    function setContriberNotes()
    {
        $infoAdd = $this->xml->getInfAdic();
        $this->pdf->SetFont($this->font, '', 8);
        $this->pdf->Cell($this->pageWidth, 5, 'OBSERVAÇÕES DO CONTRIBUINTE');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, $infoAdd->infCpl);
        $this->pdf->Ln();
        $total = $this->xml->getTotal();
        $this->pdf->MultiCell(60, 5, 'Valor aproximado dos tributos deste cupom (Conforme Lei Fed. 12.741/2012)', 0, 'L', 0, 0);
        $this->pdf->MultiCell(18, 5, number_format((float)$total->vCFeLei12741, 2, ',', '.'), 0, 'R');
        $this->pdf->Ln();
    }

    function setCFeId()
    {
        $id = $this->xml->getId();
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 7.2);
        $formt = sprintf("%s %s %s %s %s %s %s %s %s %s %s %s", substr($id, 0, 4), substr($id, 4, 4), substr($id, 8, 4), substr($id, 12, 4), substr($id, 16, 4), substr($id, 18, 4), substr($id, 20, 4), substr($id, 24, 4), substr($id, 28, 4), substr($id, 32, 4), substr($id, 36, 4), substr($id, 40, 4));
        $this->pdf->Cell($this->pageWidth, 5, $formt, 0, 'C');
    }

    function setBarcode()
    {
        $id = $this->xml->getId();
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
        $this->pdf->write1DBarcode(substr($id, 0, 20), 'C128A', 15, '', 50, 15, 0.4, $style, 'C');
        $this->pdf->Ln(15);
        $this->pdf->write1DBarcode(substr($id, 20, 20), 'C128A', 15, '', 50, 15, 0.4, $style, 'C');
        $this->pdf->Ln();
    }

    function setQRCode()
    {
        $ide = $this->xml->getIde();
        $dest = $this->xml->getDest();
        $style = array(
            'border' => 5,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
        $this->pdf->SetY($this->pdf->GetY() + 13);
        $this->pdf->write2DBarcode($ide->assinaturaQRCODE, 'QRCODE,H', 2, $this->pdf->GetY(), 37, 37, $style, 'C');
    }

    function setSATData()
    {
        $ide = $this->xml->getIde();
        $dest = $this->xml->getDest();
        $yValue = $this->pdf->GetY();
        $this->pdf->Text(45, $yValue, 'Consumidor');
        $yValue += 3.5;
        $this->pdf->Text(45, $yValue, $dest->xNome);
        $cpf = $dest->CPF ? $dest->CPF : '';
        $cnpj = $dest->CNPJ ? $dest->CNPJ : '';
        $numDoc = $cpf ? $cpf : $cnpj;
        if ($numDoc) {
            $yValue += 3.5;
            $this->pdf->Text(45, $yValue, "Doc: {$numDoc}");
        }
        $yValue += 3.5;
        $this->pdf->Text(45, $yValue, "Nº Serie SAT: {$ide->nserieSAT}");
        $yValue += 3.5;
        $dtEmi = \DateTime::createFromFormat("Ymd", $ide->dEmi);
        $hEmi = \DateTime::createFromFormat("His", $ide->hEmi);
        $this->pdf->Text(45, $yValue, "{$dtEmi->format("d/m/Y")} {$hEmi->format("H:i:s")}");
        $this->pdf->SetY($this->pdf->GetY() + 6);
        $this->pdf->Cell(40, 5, '');
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
