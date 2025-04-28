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
}$selectedMonth = isset($_REQUEST['monthFilter']) ? (int)$_REQUEST['monthFilter'] : '';
$selectedYear = isset($_REQUEST['yearFilter']) ? (int)$_REQUEST['yearFilter'] : '';

$query = "
    SELECT 
        u.user_id, 
        u.email,
        u.username, 
        u.created_at
    FROM user u
    WHERE u.status = 'Verified'
";

if (!empty($selectedMonth)) {
    $query .= " AND MONTH(u.created_at) = $selectedMonth";
}

if (!empty($selectedYear)) {
    $query .= " AND YEAR(u.created_at) = $selectedYear";
} else {
    $query .= " AND YEAR(u.created_at) = 2025";
    $selectedYear = 2025;
}

$query .= " ORDER BY u.created_at DESC";

$result = $conn->query($query);
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('First Time Users Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'First Time Users Report';
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
$pdf->Cell(20, 8, '   ' .'Bil', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(80, 8, '   ' .'Username', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(118, 8, '   ' .'Email', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(60, 8, '   ' .'Created At', 1, 1, 'L',true);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);
$bil = 1;

while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    // Auto page break
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }
    
    $pdf->Cell(20, 8, '   ' .$bil++, 1, 0, 'L',true);
    $pdf->Cell(80, 8, '   ' .$row['username'], 1, 0, 'L',true);
    $pdf->Cell(118, 8, '   ' .$row['email'], 1, 0, 'L',true);
    $pdf->Cell(60, 8, '   ' .$row['created_at'], 1, 1, 'L',true);

    $fill = !$fill;
}

if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}
// Output PDF
$pdf->Output('firsttime_users.pdf', 'I');

$conn->close();
?>
