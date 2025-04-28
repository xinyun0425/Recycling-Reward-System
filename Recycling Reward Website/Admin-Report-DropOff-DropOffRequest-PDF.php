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

$query = "
    SELECT 
        DATE_FORMAT(do.dropoff_date, '%Y-%m') AS month, 
        do.dropoff_date, 
        u.username, 
        i.item_name, 
        d.quantity, 
        l.location_name
    FROM dropoff do
    LEFT JOIN user u ON do.user_id = u.user_id
    LEFT JOIN item_dropoff d ON do.dropoff_id = d.dropoff_id
    LEFT JOIN item i ON i.item_id = d.item_id
    LEFT JOIN location l ON do.location_id = l.location_id
    WHERE do.status = 'Complete' AND
        1=1

";

if (!empty($selectedMonth)) {
    $query .= " AND MONTH(do.dropoff_date) = $selectedMonth";
}

if (!empty($selectedYear)) {
    $query .= " AND YEAR(do.dropoff_date) = " . intval($selectedYear);
} else {
    $query .= " AND YEAR(do.dropoff_date) = 2025";
    $selectedYear = 2025;
}

// Order the results
$query .= " ORDER BY month ASC, do.dropoff_date ASC";

$result = $conn->query($query);

// Create new PDF document using TCPDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Drop-off Request Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'Drop-off Request Report';
if (!empty($selectedMonth)) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ({$monthName} {$selectedYear})";
} else {
    $reportTitle .= " ({$selectedYear})";
}

$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);

// Table Headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(45, 8, '   ' .'Drop-off Date', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(60, 8, '   ' .'Username', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(60, 8, '   ' .'Item', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(40, 8, '   ' .'Quantity', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(73, 8, '   ' .'Location', 1, 1, 'L',true);


$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);
while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    // Auto page break
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }
    
    $pdf->Cell(45, 8, '   ' . $row['dropoff_date'], 1, 0, 'L', true);
    $pdf->Cell(60, 8, '   ' . $row['username'], 1, 0, 'L',true);
    $pdf->Cell(60, 8, '   ' . $row['item_name'], 1, 0, 'L',true);
    $pdf->Cell(40, 8, '   ' . $row['quantity'], 1, 0, 'L',true);
    $pdf->Cell(73, 8, '   ' . $row['location_name'], 1, 1, 'L',true);

    $fill = !$fill;
}
if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}


// Output PDF
$pdf->Output('dropoff_request_report.pdf', 'I');

$conn->close();
?>
