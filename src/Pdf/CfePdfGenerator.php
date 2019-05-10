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
    //Cancelled
    private $idCanc;
    private $isCancelled = false;
    private $ideCanc;

    function __construct(XmlReader $xml, $isCancelled = false, $pageWidth = 75, $font = 'helvetica')
    {
        $this->id = $xml->getId();
        $this->ide = $xml->getIde();
        $this->emit = $xml->getEmit();
        $this->dest = $xml->getDest();
        $this->total = $xml->getTotal();
        $this->det = $xml->getDet();
        $this->payments = $xml->getPgto();
        $this->infoAdd = $xml->getInfAdic();
        $this->pageWidth = $pageWidth;
        $this->font = $font;
        $this->pdf = new Pdf();
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->isCancelled = $isCancelled;
        if (!$this->isCancelled) {
            $this->createPDF();
        }
    }

    function setCancelCoupon(XmlReader $xml)
    {
        //Check cancelled coupon id
        if ($xml->getIdCanc() !== $this->id){
            throw new \Exception('A chave chCanc informada no XML de cancelamento deve ser identica a chave do documento cancelado');
        }
        $this->idCanc = $xml->getId();
        $this->ideCanc = $xml->getIde();
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
        $this->setEmitData();
        $this->pdf->SetFont($this->font, 'B', 8);
        $this->pdf->MultiCell($this->pageWidth, 5, "EXTRATO Nº {$this->ide->nCFe} do CUPOM FISCAL ELETRÔNICO - SAT", 0, 'C', 0);
        $this->pdf->Ln();
    }

    function setHeaderCancelled()
    {
        $this->setEmitData();
        $this->pdf->SetFont($this->font, 'B', 8);
        $this->pdf->MultiCell($this->pageWidth, 5, "EXTRATO Nº {$this->ide->nCFe} do CUPOM FISCAL ELETRÔNICO - SAT", 0, 'C', 0);
        $this->pdf->MultiCell($this->pageWidth, 5, "CANCELAMENTO", 0, 'C', 0);
        $this->setDividerLine();
    }

    function setEmitData()
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
            $this->pdf->Cell(20, 3.5, $this->formatFloat($prod->vItem), 0, 0, 'R');
            $this->pdf->Ln();
            $index++;
        }
        $this->pdf->Ln();
    }

    function setTotals()
    {
        $this->pdf->SetFont($this->font, 'B', 12);
        $this->pdf->Cell(45, 5, 'TOTAL R$');
        $this->pdf->Cell(25, 5, $this->formatFloat($this->total->vCFe), 0, 0, 'R');
        $this->pdf->Ln();
    }

    function setPayments()
    {
        $this->pdf->SetFont($this->font, '', 8);
        foreach ($this->payments as $payment) {
            $this->pdf->Cell(45, 5, PaymentTypes::byCode($payment->MP->cMP));
            $this->pdf->Cell(25, 5, $this->formatFloat($payment->MP->vMP), 0, 0, 'R');
            $this->pdf->Ln();
        }
        if ($this->payments->vTroco > 0) {
            $this->pdf->SetFont($this->font, '', 10);
            $this->pdf->Cell(45, 5, 'Troco R$');
            $this->pdf->Cell(25, 5, $this->formatFloat($this->payments->vTroco), 0, 0, 'R');
            $this->pdf->Ln();
        }
    }

    function setCancelledCouponData()
    {
        $this->pdf->SetFont($this->font, 'B', 7);
        $this->pdf->MultiCell($this->pageWidth, 5, "DADOS DO CUPOM FISCAL ELETRÔNICO CANCELADO", 0, 'C', 0);
        $this->pdf->SetFont($this->font, '', 7);
        $this->pdf->MultiCell($this->pageWidth, 5, "CPF/CNPJ Consumidor: " . $this->getNumDoc(), 0, 'C', 0);
        $this->pdf->MultiCell($this->pageWidth, 5, "Razão Social/ Nome Consumidor: " . $this->dest->xNome, 0, 'C', 0);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, 'TOTAL R$', 0, 0, 'R');
        $this->pdf->SetFont($this->font, 'B', 7);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, $this->formatFloat($this->total->vCFe), 0, 0, 'L');
        $this->setDividerLine();
        $this->pdf->SetFont($this->font, '', 7);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, 'SAT Nº', 0, 0, 'R');
        $this->pdf->SetFont($this->font, 'B', 7);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, $this->ide->nCFe, 0, 0, 'L');
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 7);
        $dtEmi = \DateTime::createFromFormat("Ymd", $this->ide->dEmi);
        $hEmi = \DateTime::createFromFormat("His", $this->ide->hEmi);
        $this->pdf->MultiCell($this->pageWidth, 5, "{$dtEmi->format("d/m/Y")} {$hEmi->format("H:i:s")}", 0, 'C');
        $this->setCFeId($this->id);
        $this->setBarcode($this->id);
        $this->setQRCodeCoupon(18);
        $this->setDividerLine();
    }

    function setCancelCouponData()
    {
        $this->pdf->SetFont($this->font, '', 7);
        $this->pdf->MultiCell($this->pageWidth, 5, "DADOS DO CUPOM FISCAL ELETRÔNICO DE CANCELAMENTO", 0, 'C', 0);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, 'SAT Nº', 0, 0, 'R');
        $this->pdf->SetFont($this->font, 'B', 7);
        $this->pdf->Cell($this->pageWidth * 0.5, 5, $this->ideCanc->nCFe, 0, 0, 'L');
        $this->pdf->Ln();
        $dtEmi = \DateTime::createFromFormat("Ymd", $this->ideCanc->dEmi);
        $hEmi = \DateTime::createFromFormat("His", $this->ideCanc->hEmi);
        $this->pdf->SetFont($this->font, '', 7);
        $this->pdf->MultiCell($this->pageWidth, 5, "{$dtEmi->format("d/m/Y")} {$hEmi->format("H:i:s")}", 0, 'C');
        $this->setCFeId($this->idCanc);
        $this->setBarcode($this->idCanc);
        $this->setQRCodeCancelCoupon(18);
        $this->setDividerLine();
        $this->pdf->MultiCell($this->pageWidth, 5, 'Consulte o QR Code pelo aplicativo "De olho na nota" disponível na AppStore (Apple) e PlayStore(Android)', 0, 'C', 0);
    }

    function setContriberNotes()
    {
        $this->pdf->SetFont($this->font, '', 8);
        $this->pdf->Cell($this->pageWidth, 5, 'OBSERVAÇÕES DO CONTRIBUINTE');
        $this->pdf->Ln();
        $this->pdf->Cell($this->pageWidth, 5, $this->infoAdd->infCpl);
        $this->pdf->Ln();
        $this->pdf->MultiCell(45, 5, 'Valor aproximado dos tributos deste cupom (Conforme Lei Fed. 12.741/2012)', 0, 'L', 0, 0);
        $this->pdf->MultiCell(25, 5, $this->formatFloat($this->total->vCFeLei12741), 0, 'R');
        $this->pdf->Ln();
    }

    function setCFeId($id)
    {
        $this->pdf->Ln();
        $this->pdf->SetFont($this->font, '', 6.5);
        $formt = sprintf("%s %s %s %s %s %s %s %s %s %s %s %s", substr($id, 0, 4), substr($id, 4, 4), substr($id, 8, 4), substr($id, 12, 4), substr($id, 16, 4), substr($id, 18, 4), substr($id, 20, 4), substr($id, 24, 4), substr($id, 28, 4), substr($id, 32, 4), substr($id, 36, 4), substr($id, 40, 4));
        $this->pdf->Cell($this->pageWidth, 5, $formt, 0, 'C');
    }

    function setBarcode($barcode)
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
            'bgcolor' => false,
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );
        $this->pdf->Ln();
        $this->pdf->write1DBarcode($barcode, 'C128C', 2, '', 70, 10, 0.4, $style, 'C');
        $this->pdf->Ln();
    }

    function setQRCodeCoupon($xPosition = 2)
    {
        $qrCodeString = "{$this->id}|{$this->ide->dEmi}{$this->ide->hEmi}|{$this->total->vCFe}|{$this->getNumDoc()}|{$this->ide->assinaturaQRCODE}";
        $this->setQRCode($qrCodeString, $xPosition, $this->pdf->GetY() + 10);
    }

    function setQRCodeCancelCoupon($xPosition = 18)
    {
        $qrCodeString = "{$this->idCanc}|{$this->ideCanc->dEmi}{$this->ideCanc->hEmi}|{$this->total->vCFe}|{$this->getNumDoc()}|{$this->ideCanc->assinaturaQRCODE}";
        $this->setQRCode($qrCodeString, $xPosition, $this->pdf->GetY() + 10);
    }

    function setQRCode($qrCodeString, $xPosition, $yPosition)
    {
        $style = array(
            'border' => 5,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1
        );
        $this->pdf->write2DBarcode($qrCodeString, 'QRCODE,L', $xPosition, $yPosition, 37, 37, $style, 'C');
        $this->pdf->SetY($yPosition + 37);
    }

    function getNumDoc()
    {
        $cpf = $this->dest->CPF ? $this->dest->CPF : '';
        $cnpj = $this->dest->CNPJ ? $this->dest->CNPJ : '';
        return $cpf ? $cpf : $cnpj;
    }

    function setSATData()
    {
        $yValue = $this->pdf->GetY() - 37;
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

    function formatFloat($value, int $decimals = 2)
    {
        return number_format((float)$value, $decimals, ',', '.');
    }

    function createPDF()
    {
        $this->createPage();
        if (!$this->isCancelled) {
            $this->setHeader();
            $this->setProducts();
            $this->setTotals();
            $this->setPayments();
            $this->setContriberNotes();
            $this->setCFeId($this->id);
            $this->setBarcode($this->id);
            $this->setQRCodeCoupon();
            $this->setSATData();
        } else {
            $this->setHeaderCancelled();
            $this->setCancelledCouponData();
            $this->setCancelCouponData();
        }
    }

    function getPDF($filename, $dest = 'I')
    {
        $this->pdf->Output($filename, $dest);
    }
}
