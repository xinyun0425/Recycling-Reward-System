<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
require_once('tcpdf/TCPDF-main/tcpdf.php');

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

// DB connection
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

// Query
$query = "
    SELECT 
        DATE_FORMAT(pr.datetime_submit_form, '%Y-%m') AS month, 
        pr.datetime_submit_form AS pickup_date, 
        u.username,
        d.driver_name,
        i.item_name,
        p.quantity
    FROM pickup_request pr
    
    JOIN user u ON pr.user_id = u.user_id
    JOIN driver d ON pr.driver_id = d.driver_id
    JOIN item_pickup p ON pr.pickup_request_id = p.pickup_request_id
    JOIN item i ON i.item_id = p.item_id
    WHERE pr.status ='Completed' AND
        1=1
";

if (!empty($selectedMonth)) {
    $query .= " AND MONTH(pr.datetime_submit_form) = $selectedMonth";
}

if (!empty($selectedYear)) {
    $query .= " AND YEAR(pr.datetime_submit_form) = $selectedYear";
}else {
    $query .= " AND YEAR(pr.datetime_submit_form) = 2025";
    $selectedYear = 2025;
}

// Order the results
$query .= " ORDER BY month ASC, pr.datetime_submit_form ASC";


$result = $conn->query($query);

// Create PDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Pickup Request Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');


// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'Pickup Request Report';
if (!empty($selectedMonth)) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ({$monthName} {$selectedYear})";
} else {
    $reportTitle .= " ({$selectedYear})";
}
$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);

// Table headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(45, 8, '   ' .'Pickup Date', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(46, 8, '   ' .'Username', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(45, 8, '   ' .'Driver Name', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(90, 8, '   ' .'Item Name', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(52, 8, '   ' .'Quantity', 1, 1, 'L', true);

// Table rows
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);

while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);

    $pdf->Cell(45, 8, '   ' . date('Y-m-d', strtotime($row['pickup_date'])), 1, 0, 'L', true);
    $pdf->Cell(46, 8, '   ' .$row['username'], 1, 0, 'L', true);
    $pdf->Cell(45, 8, '   ' .$row['driver_name'], 1, 0, 'L', true);
    $pdf->Cell(90, 8, '   ' .$row['item_name'], 1, 0, 'L', true);
    $pdf->Cell(52, 8, '   ' .$row['quantity'], 1, 1, 'L', true);

    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }
    $fill = !$fill;
}
if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}

// Output
$pdf->Output('pickup_request_report.pdf', 'I');
$conn->close();
?>
