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

$pdf->Image('logo_seai_exeed.PNG', 145, 10, 50);

$pdf->SetFont('Arial', '', 14);
$pdf->SetY(45);
$pdf->WriteHTML('<p align="center">Form B</p>');


$pdf->SetFont('Arial', '', 14);
$pdf->SetY(53);
$pdf->WriteHTML('<p align="center">Declaration of SME Qualification</p>');

$pdf->SetFont('Arial', '', 7);
$pdf->SetY(58);
$pdf->WriteHTML('<p align="center">EXEED applicants who qualify as an SME must complete this declaration. For guidance on completing the declaration please see refer to page 46 in the following </p>');

$pdf->SetX(15.5);
$pdf->WriteHTML('<p align="left">document <a href="http://ec.europa.eu/DocsRoom/documents/10109/attachments/1/translations/en/renditions/pdf">http://ec.europa.eu/DocsRoom/documents/10109/attachments/1/translations/en/renditions/pdf</a></p>');


$pdf->SetXY(15.5, 72);

$pdf->SetFont('Arial', 'B', 9);
$pdf->WriteHTML('<p align="left">Precise identification of the applicant enterprise </p>');

$pdf->SetFont('Arial', '', 8);

$pdf->SetXY(15.5, 78);
$pdf->WriteHTML('<p align="left">Name or Business name ........................................................................................................................................................</p>');

$pdf->SetXY(15.5, 84);
$pdf->WriteHTML('<p align="left">Address (of registered office) .................................................................................................................................................</p>');

$pdf->SetXY(15.5, 90);
$pdf->WriteHTML('<p align="left">Tax registration number   .......................................................................................................................................................</p>');

$pdf->SetXY(15.5, 96);
$pdf->WriteHTML('<p align="left">Names and titles of the principal director(s) (1) .....................................................................................................................</p>');

$pdf->SetXY(15.5, 110);
$pdf->WriteHTML('<p align="left"><b>Type of enterprise</b>(see explanatory note)</p>');

$pdf->SetXY(15.5, 115);
$pdf->WriteHTML('<p align="left">Tick to indicate which case(s) applies to the applicant enterprise: </p>');

$pdf->Rect(22, 125, 2.5, 2.5);


$pdf->SetXY(25, 124);
$pdf->WriteHTML('<p align="left">Autonomous enterprise</p>');

$pdf->SetXY(90, 124);
$pdf->WriteHTML('<p align="center">In this case the data filled in the box below result from the accounts of the </p>');

$pdf->SetXY(92.5, 127);
$pdf->WriteHTML('<p align="left">applicant enterprise only. Fill in the declaration only, without annex.</p>');


$pdf->Rect(22, 132, 2.5, 2.5);


$pdf->SetXY(25, 131);
$pdf->WriteHTML('<p align="left">Partner enterprise</p>');

$pdf->SetXY(90, 134);
$pdf->WriteHTML('<p align="center">Fill in and attach the annex (and any additional sheets), then complete the </p>');

$pdf->SetXY(92.5, 137);
$pdf->WriteHTML('<p align="left">declaration by copying the results of the calculations into the box below.</p>');


$pdf->Rect(22, 139, 2.5, 2.5);
$pdf->SetXY(25, 138);
$pdf->WriteHTML('<p align="left">Linked enterprise</p>');


$pdf->SetFont('Arial', 'B', 9);

$pdf->SetXY(15.5, 145);
$pdf->WriteHTML('<p align="left">Data used to determine the category of enterprise</p>');

$pdf->SetFont('Arial', '', 9);

$pdf->SetXY(15.5, 150);
$pdf->WriteHTML('<p align="left">Calculated according to Article 6 of the Annex to the Commission Recommendation 2003/361/EC on the SME definition.</p>');


$pdf->Line(17, 160, 195, 160);

$pdf->SetXY(19, 160);
$pdf->WriteHTML('<p align="left">Reference period (*)</p>');

$pdf->Line(17, 165, 195, 165);

$pdf->SetFont('Arial', 'B', 9);

$pdf->Line(75, 165, 75, 175);
$pdf->Line(135, 165, 135, 175);

$pdf->Line(17, 170, 195, 170);
$pdf->Line(17, 175, 195, 175);

$pdf->SetXY(19, 165);
$pdf->WriteHTML('<p align="left">Headcount (AWU)</p>');

$pdf->SetXY(77, 165);
$pdf->WriteHTML('<p align="left">Annual turnover (**)</p>');

$pdf->SetXY(137, 165);
$pdf->WriteHTML('<p align="left">Balance sheet total (**)</p>');


$pdf->SetFont('Arial', '', 6);

$pdf->SetXY(15.5, 180);
$pdf->WriteHTML('<p align="left">(*) All data must be relating to the last approved accounting period and calculated on an annual basis. In the case of newly-established enterprises whose accounts have not yet been</p>');

$pdf->SetXY(15.5, 183);
$pdf->WriteHTML('<p align="left">approved, the data to apply shall be derived from a reliable estimate made in the course of the financial year. This Declaration must be accompanied by a set of latest Audited</p>');

$pdf->SetXY(15.5, 186);
$pdf->WriteHTML('<p align="left">Accounts.</p>');

$pdf->SetXY(15.5, 189);
$pdf->WriteHTML('<p align="left">(**) EUR 1 000.</p>');


$pdf->SetFont('Arial', 'B', 9);


$pdf->SetXY(15.5, 195);
$pdf->WriteHTML('<p align="left">Important:</p>');


$pdf->SetFont('Arial', '', 9);

$pdf->SetXY(17, 200);
$pdf->WriteHTML('<p align="left">Compared to the previous accounting period there </p>');

$pdf->SetXY(17, 204);
$pdf->WriteHTML('<p align="left">is a change regarding the data, which could result</p>');

$pdf->SetXY(17, 208);
$pdf->WriteHTML('<p align="left">in a change of category of the applicant enterprise </p>');

$pdf->SetXY(17, 212);
$pdf->WriteHTML('<p align="left">(micro, small, medium-sized or big enterprise).</p>');


$pdf->Rect(110, 201, 2.5, 2.5);

$pdf->SetXY(113, 200);
$pdf->WriteHTML('<p align="left"><b>No</b></p>');

$pdf->Rect(110, 209, 2.5, 2.5);
$pdf->SetXY(113, 208);
$pdf->WriteHTML('<p align="left"><b>Yes</b> (in this case fill in and attach a declaration </p>');

$pdf->SetXY(109, 212);
$pdf->WriteHTML('<p align="left">regarding the previous accounting period (2) )</p>');


$pdf->SetXY(15.5, 219);
$pdf->WriteHTML('<p align="left">I have uploaded / attached a set of latest <b><i><u>Audited</b></i></u> Accounts    </p>');
$pdf->Rect(110, 220, 2.5, 2.5);

$pdf->SetXY(15.5, 228);
$pdf->WriteHTML('<p align="left"><b>Authorisation:</b></p>');

$pdf->SetXY(15.5, 234);
$pdf->WriteHTML('<p align="left">Name and position of the signatory, being authorised to represent the enterprise:................................................................ </p>');

$pdf->SetXY(15.5, 238);
$pdf->WriteHTML('<p align="left">................................................................................................................................................................................................ </p>');

$pdf->SetXY(15.5, 243);
$pdf->WriteHTML('<p align="left">I declare on my honour the accuracy of this declaration and of any annexes thereto. </p>');


$pdf->SetXY(15.5, 253);
$pdf->WriteHTML('<p align="left"><b>Signature:</b></p>');
$pdf->Line(34, 257, 100, 257);

$pdf->SetXY(15.5, 261);
$pdf->WriteHTML('<p align="left"><b>Dated:</b></p>');
$pdf->Line(28, 265, 100, 265);

$pdf->SetFont('Arial', '', 6);

$pdf->SetXY(15.5, 268);
$pdf->WriteHTML('<p align="left">(1) Chairman (CEO), Director-General or equivalent. </p>');

$pdf->SetXY(15.5, 271);
$pdf->WriteHTML('<p align="left">(2) Definition, Article 4 paragraph 2 of the annex to Commission Recommendation 2003/361/EC </p>');


$pdf->Output();
?>