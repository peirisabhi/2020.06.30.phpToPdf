<?php

//echo "Doc submited: ".$_POST['sig_dat'];


//contact id and name array
//$person_data = $_POST['filled_by_name'];
//seprate date . before | is the ctc id after | is the name
$person_data = explode("|",$_POST['filled_by_name']);
$ctc_id = $person_data[0];
$filled_by_name = $person_data[1];



$sig_data = $_POST['sig_dat'];
$filled_date = $_POST['filled_date'];
$budget = $_POST['bdgt'];
$c_id = $_POST['cid'];




//get logo location

include 'addon_dbcon_si.php';

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

mysqli_set_charset($conn,"utf8");

//Start get compt dt form fields
$sql2 = "SELECT bus_name FROM v3_clientsSummary  WHERE client_id = '$c_id'";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    // output data of each row
    while($row = $result2->fetch_assoc()) {
      $bus_name = $row["bus_name"];


$client_logo = '../../iee.dfrontier.net.file_sys/ID-'.$c_id.' '.$bus_name.'/logo.png';
if (file_exists($client_logo))
{
     $clinet_logo_b64image = base64_encode(file_get_contents($client_logo));
     
}
else{
  exit("No client logo");
}


}
}
else{


$error_code = "Error - XX: Error getting business name from DB to find logo";
  
  $entered_data = "Error details: ".$conn->error;

    require 'sec_addon_error_mailer.php';
    $conn->close();
    exit("Error - XX");
}


//end get logo location



//get contact details for footer
$sql3 = "SELECT ctc_id, client_id, first_name, last_name, address_l1, address_l2, address_l3, tel, email, website, designation  FROM v3_clients_contact_details  WHERE client_id = '$c_id' AND ctc_id = '$ctc_id'";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    // output data of each row
    while($row = $result3->fetch_assoc()) {
      $fn = $row["first_name"];
      $ln = $row["last_name"];
      $ad1 = $row["address_l1"];
      $ad2 = $row["address_l2"];
      $ad3 = $row["address_l3"];
      $tel = $row["tel"];
      $em = $row["email"];
      $wb = $row["website"];
      $desig = $row["designation"];

}
}
else{
    exit("No contact name entry or No client contact details");
}


//end get contact details for footer










//Check signuature image
if (preg_match('/^data:image\/(\w+);base64,/', $sig_data, $type)) {
    $sig_data = substr($sig_data, strpos($sig_data, ',') + 1);
    $type = strtolower($type[1]); // jpg, png, gif

   // if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
      if (!in_array($type, [ 'png' ])) {
        throw new \Exception('Invalid image type');
    }

    $sig_data = base64_decode($sig_data);

    if ($sig_data === false) {
        throw new \Exception('base64_decode failed');
    }
} else {
    throw new \Exception('Mismatch data URI with image data');
}

file_put_contents("sig_image.{$type}", $sig_data);
//end check signature image



require('./fpdf/fpdf.php');
$pdf = new FPDF('P','mm','A4');
//SetMargins(float left, float top [, float right])
$pdf->SetMargins(20,20,20);

$pdf->AddPage();



$pdf->Image($client_logo,20,10,0,20,'PNG');
//$pdf->Image("img/logo_seai_exeed.png",20,10,0,20,'PNG');
$pdf->Image("img/logo_seai_exeed.png",150,10,0,20,'PNG');

$pdf->ln(34);
$pdf->SetFont("Arial","B",12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,12,"DECLARATION OF FINANCIAL RESOURCES AVAILABILITY FOR THE PROJECT",0,1,"L");


$pdf->ln(8);
$pdf->SetFont("Arial","",9);
$pdf->MultiCell(0,6,'I, '.$filled_by_name.', in my capacity as Managing Director/Finance Director/(or equivalent) of '.$bus_name.' (hereinafter called the "Organisation") confirm to the Sustainable Energy Authority of Ireland that the Organisation has access to an amount of '.$budget.' in place tomeet the total costs of the project outlined in the Organisation\'s grant application to the EXEED grant scheme 2018.',0,"J");


$pdf->ln(10);
$pdf->SetFont("Arial","B",9);
$pdf->MultiCell(0,6,'Managing Director/Finance Director (or equivalent)',0,"L");




$pdf->Image("sig_image.png",20,120,0,25,'PNG');



$pdf->ln(35);
$pdf->SetFont("Arial","",9);
$pdf->MultiCell(0,6,$fn." ".$ln,0,"L");
$pdf->MultiCell(0,6,$desig,0,"L");
$pdf->MultiCell(0,6,$filled_date,0,"L");




$pdf->ln(89);
$pdf->SetFont("Arial","B",9);
$pdf->MultiCell(0,6,$bus_name,0,"C");

$pdf->SetFont("Arial","",9);
$pdf->MultiCell(0,6,$ad1.', '.$ad2.', '.$ad3,0,"C");
$pdf->MultiCell(0,6,'Tel: '.$tel.' | Web: '.$wb.' | Email: '.$em,0,"C");

//end of pdf


$pdf->Output('I',"test.pdf");



//START SAVING FILE


//set time on file
date_default_timezone_set("Europe/Dublin");
$local_ts = date("Y_m_d h_i_sa");


$output_file_name = 'ID-'.$c_id.' Declaration of financial resources '.$local_ts.'.pdf';

//make dir to save the folder. If exists nothing happens

$dir_to_save_file = '../../iee.dfrontier.net.file_sys/ID-'.$c_id.' '.$bus_name.'/Support Documents';

//check if dir exists

if(is_dir($dir_to_save_file)) {
  echo "<br />Checking folder - Support Documents - OK";
  $pdf->Output('F',$dir_to_save_file.'/'.$output_file_name);
  
} else {
  echo "<br />Creating folder - Support Documents - OK";

  mkdir('../../iee.dfrontier.net.file_sys/ID-'.$c_id.' '.$bus_name.'/Support Documents');
  $pdf->Output('F',$dir_to_save_file.'/'.$output_file_name);

}

//clear dir check status from php cache
clearstatcache();
//delete signature image
unlink("sig_image.{$type}");



//check if file exists

if (file_exists($dir_to_save_file.'/'.$output_file_name)) {
    echo "<br />The file exists";


//Start db entry

$sql = "INSERT INTO v3_support_docs_created (client_id,document_type,document_dir, document_file_name )
VALUES ('$c_id','Declaration of financial resources','$dir_to_save_file','$output_file_name')";

if ($conn->query($sql) === TRUE) {
    $last_id = $conn->insert_id;
    echo "<br />Doc ID: ".$last_id." - ok";

//Start redirect to doc page and use js to open new pop message to show pdf

$url_to_redirect = 'a_view_support_doc?cn='.$c_id.'&doc='.$last_id;

echo '<META HTTP-EQUIV=Refresh CONTENT="0; URL='.$url_to_redirect.'">';

}
else
{

}

//end db entry    

} else {
    echo "<br />The file doesn't seem to have been saved correctly. Please contact technical support.";
    
$error_code = "Error - 35: Processing Declaration of financial resources document. Getting sig, creating pdf, creating folder and saving file should have worked but system could not verfiy and update database.";
    
    $entered_data = "Error details: ";

    require 'sec_addon_error_mailer.php';
    exit();
}


//if yes update databse with file id
//redirect to file list page and then reidret to open file.
//if click back will go to file list page




$conn->close();

?>