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
}$selectedRange = $_POST['rangeFilter'] ?? 'all';

$conditions = "";
if ($selectedRange !== 'all') {
    if ($selectedRange === '501+') {
        $conditions = "WHERE points >= 501";
    } else {
        list($min, $max) = explode('-', $selectedRange);
        $min = intval($min);
        $max = intval($max);
        $conditions = "WHERE points BETWEEN $min AND $max";
    }
}

$query = "
    SELECT 
        username,
        points
    FROM user
    $conditions
    ORDER BY points DESC
";

$result = $conn->query($query);

// Create new PDF document using TCPDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('User Points Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Determine range label for title
switch ($selectedRange) {
    case 'all':
        $rangeLabel = 'All';
        break;
    case '501+':
        $rangeLabel = 'More than 500';
        break;
    default:
        $rangeLabel = str_replace('-', ' to ', $selectedRange) . ' Points';
        break;
}

// Add Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);
$reportTitle = 'User Points Report (' . $rangeLabel . ')';
$pdf->Cell(100, 12, $reportTitle, 0, 1, 'L');
$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);

// Table Headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(74, 145, 63);
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(30, 8, '   ' .'Bil', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(148, 8, '   ' .'Username', 1, 0, 'L',true);
$pdf->SetDrawColor(200, 200, 200); 
$pdf->Cell(100, 8, '   ' .'Points', 1, 1, 'L',true);


$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$bil = 1;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200);

while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);
    $pdf->Cell(30, 8, '   ' .$bil++, 1, 0, 'L',true);
    $pdf->Cell(148, 8, '   ' .$row['username'], 1, 0, 'L',true);
    $pdf->Cell(100, 8, '   ' .$row['points'], 1, 1, 'L',true);
    
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
// Output PDF
$pdf->Output('user_points.pdf', 'I');

$conn->close();
?>
