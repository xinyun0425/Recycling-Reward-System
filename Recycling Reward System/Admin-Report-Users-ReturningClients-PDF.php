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
$selectedYear = isset($_REQUEST['yearFilter']) && $_REQUEST['yearFilter'] !== '' ? intval($_REQUEST['yearFilter']) : null;
$selectedMonth = isset($_REQUEST['monthFilter']) && $_REQUEST['monthFilter'] !== '' ? intval($_REQUEST['monthFilter']) : null;

$filterCondition = "";
if ($selectedYear && $selectedMonth) {
    $filterCondition = "WHERE YEAR(user_summary.latest_service_date) = $selectedYear AND MONTH(user_summary.latest_service_date) = $selectedMonth";
} elseif ($selectedYear) {
    $filterCondition = "WHERE YEAR(user_summary.latest_service_date) = $selectedYear";
}


$query1 = "
    SELECT 
        u.username,
        u.email,
        user_summary.total_services,
        user_summary.latest_service_date
    FROM (
        SELECT 
            user_id,
            COUNT(*) AS total_services,
            MAX(service_date) AS latest_service_date
        FROM (
            SELECT user_id, datetime_submit_form AS service_date FROM pickup_request WHERE status = 'Completed'
            UNION ALL
            SELECT user_id, dropoff_date AS service_date FROM dropoff WHERE status = 'Complete'
        ) AS all_services
        GROUP BY user_id
        HAVING COUNT(*) > 1
    ) AS user_summary
    JOIN user u ON u.user_id = user_summary.user_id
    $filterCondition
    ORDER BY user_summary.total_services DESC
";


$result = $conn->query($query1);

// Create new PDF document using TCPDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Returning Clients Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');
// Add Report Title

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'Returning Clients Report';

if ($selectedMonth !== null) {
    $monthName = date("F", mktime(0, 0, 0, $selectedMonth, 1));
    $reportTitle .= " ($monthName $selectedYear)";
} else {
    $reportTitle .= " ($selectedYear)";
}

$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);

// Table Headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(15, 8, '   ' .'Bil', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(60, 8, '   ' .'Username', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(113, 8, '   ' .'Email', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(40, 8, '   ' .'Total Services', 1, 0, 'L', true);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(50, 8, '   ' .'Latest Service Date', 1, 1, 'L', true);

// Table Data
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);

$rowNumber = 1;

while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);

    $pdf->Cell(15, 8, '   ' . $rowNumber++, 1, 0, 'L', true);
    $pdf->Cell(60, 8, '   ' . $row['username'], 1, 0, 'L', true);
    $pdf->Cell(113, 8, '   ' . $row['email'], 1, 0, 'L', true);
    $pdf->Cell(40, 8, '   ' . $row['total_services'], 1, 0, 'L', true);
    $pdf->Cell(50, 8, '   ' . date('Y-m-d', strtotime($row['latest_service_date'])), 1, 1, 'L', true);

    $fill = !$fill;
}

if (!$hasRows) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200); 
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(278, 8, 'No data to be displayed.', 1, 1, 'C', true);
}


// Output PDF
$pdf->Output('returning_clients.pdf', 'I');

$conn->close();
?>
