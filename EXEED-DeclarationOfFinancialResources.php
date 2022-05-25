<?php

require './fpdf/fpdf.php';

class PDF extends FPDF {

    var $B = 0;
    var $I = 0;
    var $U = 0;
    var $HREF = '';
    var $ALIGN = '';

    function WriteHTML($html) {
        //HTML parser
        $html = str_replace("\n", ' ', $html);
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($a as $i => $e) {
            if ($i % 2 == 0) {
                //Text
                if ($this->HREF)
                    $this->PutLink($this->HREF, $e);
                elseif ($this->ALIGN == 'center')
                    $this->Cell(0, 5, $e, 0, 1, 'C');
                else
                    $this->Write(5, $e);
            } else {
                //Tag
                if ($e[0] == '/')
                    $this->CloseTag(strtoupper(substr($e, 1)));
                else {
                    //Extract properties
                    $a2 = explode(' ', $e);
                    $tag = strtoupper(array_shift($a2));
                    $prop = array();
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
                            $prop[strtoupper($a3[1])] = $a3[2];
                    }
                    $this->OpenTag($tag, $prop);
                }
            }
        }
    }

    function OpenTag($tag, $prop) {
        //Opening tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, true);
        if ($tag == 'A')
            $this->HREF = $prop['HREF'];
        if ($tag == 'BR')
            $this->Ln(5);
        if ($tag == 'P')
            $this->ALIGN = $prop['ALIGN'];
        if ($tag == 'HR') {
            if (!empty($prop['WIDTH']))
                $Width = $prop['WIDTH'];
            else
                $Width = $this->w - $this->lMargin - $this->rMargin;
            $this->Ln(2);
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetLineWidth(0.4);
            $this->Line($x, $y, $x + $Width, $y);
            $this->SetLineWidth(0.2);
            $this->Ln(2);
        }
    }

    function CloseTag($tag) {
        //Closing tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, false);
        if ($tag == 'A')
            $this->HREF = '';
        if ($tag == 'P')
            $this->ALIGN = '';
    }

    function SetStyle($tag, $enable) {
        //Modify style and select corresponding font
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s)
            if ($this->$s > 0)
                $style .= $s;
        $this->SetFont('', $style);
    }

    function PutLink($URL, $txt) {
        //Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }

}

$pdf = new PDF('P', 'mm', 'A4');

$pdf->AddPage();
$pdf->SetMargins(20, 20, 20);

$pdf->Image('logo_seai_exeed.PNG', 135, 10, 50);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetY(55);
$pdf->WriteHTML('<p align="center">On Company Letterhead</p>');


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetY(70);
$pdf->WriteHTML('<p align="center">DECLARATION OF FINANCIAL RESOURCES AVAILABILITY FOR THE PROJECT</p>');


$pdf->SetFont('Arial', '', 11);
$pdf->SetY(80);
$pdf->MultiCell(5, 6, 'I,', '');

$pdf->Line(24, 85, 90, 85);

$pdf->SetXY(90, 80);
$pdf->MultiCell(40, 6, ', in my capacity as', '');

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY(123, 80);
$pdf->MultiCell(80, 6, 'Managing Director/Finance Director ', '');

$pdf->SetXY(20, 88);
$pdf->MultiCell(80, 6, '/ (or equivalent) ', '');

$pdf->SetXY(50, 88);
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(10, 6, 'of', '');


$pdf->Line(55, 93, 150, 93);

$pdf->SetXY(150, 88);
$pdf->MultiCell(50, 6, '(hereinafter called "the ', '');


$pdf->SetXY(20, 96);
$pdf->MultiCell(180, 6, 'Organisation") confirm to the Sustainable Energy Authority of Ireland that the Organisation has ', '');


$pdf->SetXY(20, 104);
$pdf->MultiCell(80, 6, 'access to an amount of', '');

$pdf->Line(62, 109, 135, 109);


$pdf->SetXY(135, 104);
$pdf->MultiCell(100, 6, 'in place to meet the total costs ', '');

$pdf->SetXY(20, 112);
$pdf->MultiCell(180, 6, "of the project outlined in the Organisation's grant application to the EXEED grant scheme 2018. ", '');


$pdf->SetXY(20, 140);
$pdf->SetFont('Arial', 'B', 11);
$pdf->MultiCell(100, 6, 'Managing Director/Finance Director /(or equivalent) : ', '');


$pdf->SetXY(20, 150);
$pdf->MultiCell(100, 6, '(Print)', '');

$pdf->Line(45, 154, 100, 154);

$pdf->SetXY(20, 160);
$pdf->MultiCell(100, 6, 'Signature:', '');

$pdf->Line(45, 164, 100, 164);

$pdf->SetXY(20, 170);
$pdf->MultiCell(100, 6, 'Date:', '');

$pdf->Line(45, 174, 100, 174);

$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(20,190);
$pdf->WriteHTML('<p align="left">Where a number of organisations submit a joint application, a declaration is required from each <br>organisation</p>');


$pdf->Output();
?>