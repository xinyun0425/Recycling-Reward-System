<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
require_once('tcpdf/TCPDF-main/tcpdf.php');

class MYPDF extends TCPDF {
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

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cp_assignment";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selectedMonth = isset($_REQUEST['monthFilter']) && is_numeric($_REQUEST['monthFilter']) ? (int)$_REQUEST['monthFilter'] : '';
$selectedYear = isset($_REQUEST['yearFilter']) && is_numeric($_REQUEST['yearFilter']) ? (int)$_REQUEST['yearFilter'] : '';

$dropOffLocations = [];

$query = "
    SELECT 
        YEAR(d.dropoff_date) AS year, 
        MONTH(d.dropoff_date) AS month, 
        l.location_name AS dropoff_point,
        COUNT(*) AS total_dropoff 
    FROM dropoff d
    JOIN location l ON d.location_id = l.location_id
    WHERE d.status = 'Complete'
";

// Add filters if set
if ($selectedYear !== '') {
    $query .= " AND YEAR(d.dropoff_date) = $selectedYear";
} else {
    $query .= " AND YEAR(d.dropoff_date) = 2025";
    $selectedYear = 2025;
}
if ($selectedMonth !== '') {
    $query .= " AND MONTH(d.dropoff_date) = $selectedMonth";
}

$query .= " GROUP BY year, month, dropoff_point
            ORDER BY year DESC, month ASC, dropoff_point";

$result = $conn->query($query);

$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Drop-off Locations Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');
// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);

// Handle title formatting cleanly
$reportTitle = 'Drop-off Locations Report';
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
$pdf->Cell(60, 8, '   ' .'Month', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(178, 8, '   ' .'Drop-off Point', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(40, 8, '   ' .'Total Drop-off', 1, 1, 'L', true);
$pdf->SetDrawColor(200, 200, 200);

// Table Body
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);

while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    $monthFormatted = date("F", mktime(0, 0, 0, $row['month'], 1));

    // Auto page break
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }

    $pdf->Cell(60, 8, '   ' . $monthFormatted, 1, 0, 'L', true);
    $pdf->Cell(178, 8, '   ' . $row['dropoff_point'], 1, 0, 'L', true);
    $pdf->Cell(40, 8, '   ' . $row['total_dropoff'], 1, 1, 'L', true);

    $fill = !$fill;
}
if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}

$pdf->Output('drop-off_locations_report.pdf', 'I');

$conn->close();
?>
