<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
require_once('tcpdf/TCPDF-main/tcpdf.php'); // Ensure the correct path to TCPDF
class MYPDF extends TCPDF {
    // Custom Header with Logo
    public function Header() {
        $this->SetFillColor(120, 162, 76); // #78A24C
        $this->RoundedRect(9.5, 7.5, 47, 12, 5, '1111', 'F');
        $this->Image('User-Logo.png', 10, 10, 46);
        $this->SetFont('helvetica', 'B', 14);
        $this->Line(9.7, 25, 288, 25);

        // Date on the top-right
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(-59, 12);
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $this->Cell(50, 10, 'Date: ' . date("F j, Y, H:i"), 0, 0, 'R');

    }

    // Custom Footer
    public function Footer() {
        $this->SetY(-15); // 15mm from bottom
        $this->SetFont('helvetica', 'I', 8);
    
        // Manually set X to center the text visually
        $this->SetX(130);
    
        $this->Cell(50, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cp_assignment";
$selectedMonth = isset($_REQUEST['monthFilter']) ? $_REQUEST['monthFilter'] : '';
$selectedYear = isset($_REQUEST['yearFilter']) ? $_REQUEST['yearFilter'] : '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$queryTable = "
    SELECT 
        pr.datetime_submit_form,
        i.item_name,
        MONTH(pr.datetime_submit_form) AS month_num,
        YEAR(pr.datetime_submit_form) AS year,
        SUM(p.quantity) AS total_quantity
    FROM item_pickup p
    JOIN item i ON p.item_id = i.item_id
    JOIN pickup_request pr ON p.pickup_request_id = pr.pickup_request_id
    WHERE pr.status = 'Completed'";

if (!empty($selectedYear)) {
    $queryTable .= " AND YEAR(pr.datetime_submit_form) = " . intval($selectedYear);
}else {
    $queryTable .= " AND YEAR(pr.datetime_submit_form) = 2025";
    $selectedYear = 2025;
}

if (!empty($selectedMonth)) {
    $queryTable .= " AND MONTH(pr.datetime_submit_form) = " . intval($selectedMonth);
}

$queryTable .= " 
    GROUP BY i.item_name, month_num, year
    ORDER BY month_num ASC, pr.datetime_submit_form ASC";

$resultTable = $conn->query($queryTable);

// Create new PDF document using TCPDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Pickup Items Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');


$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);

// Add Report Title
$reportTitle = 'Pickup Items Report';
if (!empty($selectedMonth)) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ({$monthName} {$selectedYear})";
    
} else {
    $reportTitle .= " ({$selectedYear})";
}

$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(60, 8, '   ' .'Date', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(110, 8, '   ' .'Item Name', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(108, 8, '   ' .'Total Quantity', 1, 1, 'L',true);

$pdf->SetDrawColor(200, 200, 200);


$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);


while ($row = $resultTable->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    // Auto page break
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 8, '   ' .date('F', strtotime($row['datetime_submit_form'])), 1, 0, 'L',true);
    $pdf->Cell(110, 8, '   ' .$row['item_name'], 1, 0, 'L',true);
    $pdf->Cell(108, 8, '   ' .$row['total_quantity'], 1, 1, 'L',true);
    $fill = !$fill;
}

if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}


// Output PDF
$pdf->Output('pickup_items_report.pdf', 'I');

$conn->close();
?>
