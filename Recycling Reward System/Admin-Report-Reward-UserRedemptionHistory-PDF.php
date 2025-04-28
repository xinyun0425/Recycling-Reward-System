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

$userRedemptions = [];
$query1 = "
    SELECT 
        rr.collect_datetime AS date,
        MONTH(rr.collect_datetime) AS month_num,
        u.username,
        r.reward_name
    FROM redeem_reward rr
    JOIN user u ON rr.user_id = u.user_id
    JOIN reward r ON rr.reward_id = r.reward_id        
    WHERE 
        rr.status = 'Redeemed'";

if (!empty($selectedYear)) {
    $query1 .= " AND YEAR(rr.collect_datetime) = $selectedYear";
}else {
    $query1 .= " AND YEAR(rr.collect_datetime) = 2025";
    $selectedYear = 2025;
}

if (!empty($selectedMonth)) {
    $query1 .= " AND MONTH(rr.collect_datetime) = $selectedMonth";
}

$query1 .= " ORDER BY rr.collect_datetime ASC";

$result1 = $conn->query($query1);

$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('User Redemption History Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Add Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'User Redemption History Report';
if (!empty($selectedMonth)) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ({$monthName} {$selectedYear})";
} else {
    $reportTitle .= " ({$selectedYear})";
}
$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);

// Set Header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(50, 8, '   ' .'Collect Date', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(170, 8, '   ' .'Username', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(58, 8, '   ' .'Reward Name', 1, 1, 'L',true);

// Table Body
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);
while ($row = $result1->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    if ($pdf->GetY() > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
        $pdf->AddPage();
    }

    $pdf->Cell(50, 8, '   ' .date('Y-m-d', strtotime($row['date'])), 1, 0, 'L',true);
    $pdf->Cell(170, 8, '   ' .$row['username'], 1, 0, 'L',true);
    $pdf->Cell(58, 8, '   ' .$row['reward_name'], 1, 1, 'L',true);
    $fill = !$fill;
}

if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}


$pdf->Output('User_RedemptionHistory_report.pdf', 'I');

$conn->close();
?>
