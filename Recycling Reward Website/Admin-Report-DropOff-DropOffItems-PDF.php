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

// Database connection
$conn = new mysqli("localhost", "root", "", "cp_assignment");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selectedMonth = isset($_REQUEST['monthFilter']) ? $_REQUEST['monthFilter'] : '';
$selectedYear = isset($_REQUEST['yearFilter']) ? $_REQUEST['yearFilter'] : '';

// Base query
$query = "
    SELECT 
        YEAR(d.dropoff_date) AS year,
        MONTH(d.dropoff_date) AS month_num,
        MONTHNAME(d.dropoff_date) AS month,
        DATE(d.dropoff_date) AS dropoff_date,
        l.location_name,
        i.item_name,
        id.quantity
    FROM dropoff d
    JOIN location l ON d.location_id = l.location_id
    JOIN item_dropoff id ON d.dropoff_id = id.dropoff_id
    JOIN item i ON id.item_id = i.item_id
    WHERE d.status = 'Complete'
";

// Filter by year
if (!empty($selectedYear)) {
    $query .= " AND YEAR(d.dropoff_date) = " . intval($selectedYear);
} else {
    $query .= " AND YEAR(d.dropoff_date) = 2025";
    $selectedYear = 2025;
}

// ✅ Filter by month if selected
if (!empty($selectedMonth)) {
    $query .= " AND MONTH(d.dropoff_date) = " . intval($selectedMonth);
}

$query .= " ORDER BY d.dropoff_date;";

$result = $conn->query($query);

// Create PDF
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Drop-off Items Report.pdf');
$pdf->SetMargins(10, 30, 10);
$pdf->AddPage('L');

// Report Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetX(9.5);

// Handle title formatting cleanly
$reportTitle = 'Drop-off Items Report';
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
$pdf->SetFillColor(74, 145, 63); // Green header fill
$pdf->SetTextColor(255, 255, 255);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(60, 8, '   ' .'Month', 1, 0, 'L', true);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(168, 8, '   ' .'Item Name', 1, 0, 'L', true);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(50, 8, '   ' .'Quantity', 1, 1, 'L', true);

// Table Body
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$fill = false;
$hasRows = false;
$pdf->SetDrawColor(200, 200, 200); // Consistent border color


while ($row = $result->fetch_assoc()) {
    $hasRows = true;
    $pdf->SetFillColor($fill ? 239 : 255, $fill ? 245 : 255, $fill ? 238 : 255);

    $pdf->Cell(60, 8, '   ' . $row['month'], 1, 0, 'L', true);
    $pdf->Cell(168, 8, '   ' . $row['item_name'], 1, 0, 'L', true);
    $pdf->Cell(50, 8, '   ' . $row['quantity'], 1, 1, 'L', true);

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
$pdf->Output('dropoff_items_report.pdf', 'I');

$conn->close();
?>
