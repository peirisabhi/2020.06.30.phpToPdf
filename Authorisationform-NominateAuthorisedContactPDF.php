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

    //MultiCell with bullet
    function MultiCellBlt($w, $h, $blt, $txt, $border = 0, $align = 'J', $fill = false) {
        //Get bullet width including margins
        $blt_width = $this->GetStringWidth($blt) + $this->cMargin * 2;

        //Save x
        $bak_x = $this->x;

        //Output bullet
        $this->Cell($blt_width, $h, $blt, 0, '', $fill);

        //Output text
        $this->MultiCell($w - $blt_width, $h, $txt, $border, $align, $fill);

        //Restore x
        $this->x = $bak_x;
    }

//    table

    var $widths;
    var $aligns;

    function SetWidths($w) {
        //Set the array of column widths
        $this->widths = $w;
    }

    function SetAligns($a) {
        //Set the array of column alignments
        $this->aligns = $a;
    }

    function Row($data) {
        //Calculate the height of the row
        $nb = 0;
        for ($i = 0; $i < count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 5 * $nb;
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            //Draw the border
            $this->Rect($x, $y, $w, $h);
            //Print the text
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            //Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        //If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt) {
        //Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    function SetLineWidth($width) {
        // Set line width
        $this->LineWidth = $width;
        if ($this->page > 0)
            $this->_out(sprintf('%.2F w', $width * $this->k));
    }

}

$pdf = new PDF('P', 'mm', 'A4');

$pdf->AddPage();

$pdf->Image('logo_seai_exeed.PNG', 110, 10, 80);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetY(60);
$pdf->WriteHTML('<p align="center">EXEED 2018</p>');

$pdf->SetXY(83, 66);
$pdf->MultiCell(40, 5, 'E-mail:', 0, 'L', 0, 8);

$pdf->SetTextColor(60, 64, 198);
$pdf->SetFont('Arial', 'U', 12);
$pdf->SetXY(98, 66);
$pdf->MultiCell(40, 5, 'exeed@seai.ie:', 0, 'L', 0, 8);

$pdf->SetY(80);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->WriteHTML('<p align="left">In the interest of Data Protection, at present, only the Lead Applicant (i.e. the asset owner) <b> named on the application form </b> are authorised to discuss EXEED 2018 project specific information with SEAI. </p>');

$pdf->SetY(95);
$pdf->WriteHTML('<p align="left">Please complete the below form to include any additional persons to avoid any misunderstanding throughout the project process, i.e. EXEED expert etc.</p>');


$pdf->SetY(110);
$pdf->WriteHTML('<p align="left">By authorising these Applicants, you as Lead Applicant, will be granting permission for these people to:</p>');


$pdf->SetY(120);
$pdf->SetFont('Arial', 'B', 12);
$pdf->MultiCellBlt(10, 6, chr(149), ' ');
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(25, 120);
$pdf->MultiCell(150, 6, 'Access and provide information in relation to this Project with SEAI; and');

$pdf->SetY(125);
$pdf->SetFont('Arial', 'B', 12);
$pdf->MultiCellBlt(10, 6, chr(149), ' ');
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(25, 125);
$pdf->MultiCell(150, 6, 'Correspond with SEAI regarding this Project.');


$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(30, 55, 153);
$pdf->SetXY(10, 140);
$pdf->MultiCell(50, 6, 'Application Details');

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetXY(25, 150);
$pdf->MultiCell(50, 6, '*Lead Applicant:');

$pdf->SetXY(25, 156);
$pdf->MultiCell(50, 6, 'Project Title:');

$pdf->SetXY(25, 162);
$pdf->MultiCell(50, 6, 'EXEED Reference / s:');


$pdf->SetXY(10, 172);

$pdf->SetWidths(array(62, 62, 62));
//srand(microtime()*1000000);
//$pdf->Row(array('Name to be authorised', 'Email address', 'Phone Number'));
$pdf->Cell(62, 6, 'Name to be authorised', 1, 0, 'C');
$pdf->Cell(62, 6, 'Email address', 1, 0, 'C');
$pdf->Cell(62, 6, 'Phone Number', 1, 1, 'C');

for ($i = 1; $i < 7; $i++) {
    $pdf->Row(array(' ', ' ', ' '));
//        $pdf->Row(array($_POST["auth".$i], $_POST["auth".$i."email"], $_POST["auth".$i."tel"]));
}


//$pdf->SetFont('Arial', '', 10);
//$pdf->SetY(210);
//$pdf->WriteHTML('<p align="center"> +Please add more rows as required +</p>');

$pdf->SetXY(40, 230);
$pdf->Cell(62, 6, 'Lead Applicant Signature:', 0, 0, 'L');

$pdf->SetLineWidth(0.1);
$pdf->Line(82, 234, 160, 234);


$pdf->SetXY(70, 240);
$pdf->Cell(62, 6, 'Date:', 0, 0, 'L');

$pdf->SetLineWidth(0.1);
$pdf->Line(80, 244, 160, 244);


$pdf->SetLineWidth(0.5);
$pdf->Line(10, 260, 200, 260);


//$pdf->SetXY(40, 230);
//$pdf->Cell(62, 6, 'Page 1 of 1', 0, 0, 'L');

$pdf->Output();
?>