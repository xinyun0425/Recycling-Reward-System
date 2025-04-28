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

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cp_assignment";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selectedYear = isset($_REQUEST['yearFilter']) ? $_REQUEST['yearFilter'] : '';
$selectedMonth = isset($_REQUEST['monthFilter']) ? $_REQUEST['monthFilter'] : '';

$conditions = [];

if (!empty($selectedYear)) {
    $conditions[] = "YEAR(r.date) = '$selectedYear'";
}

if (!empty($selectedMonth)) {
    $conditions[] = "MONTH(r.date) = '$selectedMonth'";
}else {
    $selectedYear = 2025;
}

$whereClause = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$query = "
    SELECT 
        r.date,
        YEAR(r.date) AS review_year,
        LPAD(MONTH(r.date), 2, '0') AS review_month,
        r.star, 
        u.username, 
        r.review, 
        CASE 
            WHEN r.pickup_request_id IS NOT NULL THEN 'Pickup' 
            WHEN r.dropoff_id IS NOT NULL THEN 'Drop-off' 
            ELSE 'Unknown' 
        END AS service_type
    FROM review r
    LEFT JOIN pickup_request p ON r.pickup_request_id = p.pickup_request_id
    LEFT JOIN dropoff d ON r.dropoff_id = d.dropoff_id
    LEFT JOIN user u ON (p.user_id = u.user_id OR d.user_id = u.user_id)
    $whereClause
    ORDER BY r.date ASC;
";

$result = $conn->query($query);

// Create new PDF document using TCPDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Review Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'Review Report';
if (!empty($selectedMonth)) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ({$monthName} {$selectedYear})";
} else {
    $reportTitle .= " ({$selectedYear})";
}
$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetCellPadding(2); // Header padding

// Table headers

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(30, 8, 'Date', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(15, 8, 'Star', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(45, 8, 'Username', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(158, 8, 'Review', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(30, 8, 'Service Type', 1, 1, 'L', true);

// Content
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetCellPadding(2);

$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['date']));
    $review = $row['review'];
    $username = $row['username'];
    $star = $row['star'];
    $serviceType = $row['service_type'];

    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    // Store current position
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // 🔍 Pre-calculate review height
    $reviewHeight = $pdf->getStringHeight(157, $review);
    $rowHeight = max(8, $reviewHeight);

    // Draw all cells
    $pdf->MultiCell(30, $rowHeight, $date, 1, 'L', $fill, 0, $x, $y);
    $pdf->MultiCell(15, $rowHeight, $star, 1, 'L', $fill, 0, '', '', true);
    $pdf->MultiCell(45, $rowHeight, $username, 1, 'L', $fill, 0, '', '', true);
    $pdf->MultiCell(158, $rowHeight, $review, 1, 'L', $fill, 0, '', '', true);
    $pdf->MultiCell(30, $rowHeight, $serviceType, 1, 'L', $fill, 0, '', '', true);

    // Move to next row
    $pdf->Ln();
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }
    $fill = !$fill;
}
$pdf->SetCellPadding(2);
if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}
// Output PDF
$pdf->Output('review_trend_report.pdf', 'I');

$conn->close();
?>
