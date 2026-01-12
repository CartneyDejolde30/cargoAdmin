<?php
require_once __DIR__ . '/../include/db.php';

// Try to load TCPDF
$tcpdfPath = __DIR__ . '/../vendor/tcpdf/tcpdf.php';
if (file_exists($tcpdfPath)) {
    require_once $tcpdfPath;
    $hasTCPDF = true;
} else {
    $hasTCPDF = false;
}

function generateReceipt($bookingId, $conn = null) {
    global $hasTCPDF;
    
    $shouldClose = false;
    if (!$conn) {
        $conn = new mysqli("localhost", "root", "", "dbcargo");
        $shouldClose = true;
    }
    
    try {
        // Fetch booking details
        $stmt = $conn->prepare("
            SELECT 
                b.*,
                c.brand, c.model, c.car_year, c.plate_number,
                u1.fullname AS renter_name,
                u1.email AS renter_email,
                u1.phone AS renter_contact,
                p.payment_method,
                p.payment_reference,
                p.payment_status
            FROM bookings b
            LEFT JOIN cars c ON b.car_id = c.id
            LEFT JOIN users u1 ON b.user_id = u1.id
            LEFT JOIN payments p ON p.booking_id = b.id
            WHERE b.id = ?
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }
        
        // Load template
        $template = file_get_contents(__DIR__ . '/templates/receipt_template.html');
        
        // Calculate duration
        $pickup = strtotime($booking['pickup_date']);
        $return = strtotime($booking['return_date']);
        $days = max(1, ceil(($return - $pickup) / 86400));
        
        // Prepare replacements
        $receiptNo = "RCP-" . str_pad($bookingId, 6, "0", STR_PAD_LEFT);
        $bookingIdFormatted = "#BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT);
        $carName = $booking['brand'] . " " . $booking['model'] . " " . $booking['car_year'];
        
        $replacements = [
            '{{RECEIPT_NO}}' => $receiptNo,
            '{{BOOKING_ID}}' => $bookingIdFormatted,
            '{{DATE_ISSUED}}' => date('F d, Y h:i A'),
            '{{STATUS}}' => strtoupper($booking['payment_status'] ?? 'PAID'),
            '{{RENTER_NAME}}' => $booking['renter_name'],
            '{{RENTER_EMAIL}}' => $booking['renter_email'],
            '{{RENTER_CONTACT}}' => $booking['renter_contact'],
            '{{CAR_NAME}}' => $carName . " (" . $booking['plate_number'] . ")",
            '{{PICKUP_DATETIME}}' => date('F d, Y', strtotime($booking['pickup_date'])) . " at " . $booking['pickup_time'],
            '{{RETURN_DATETIME}}' => date('F d, Y', strtotime($booking['return_date'])) . " at " . $booking['return_time'],
            '{{DURATION}}' => $days . " day" . ($days > 1 ? 's' : ''),
            '{{DAILY_RATE}}' => number_format($booking['price_per_day'], 2),
            '{{BASE_AMOUNT}}' => number_format($booking['total_amount'], 2),
            '{{TOTAL_AMOUNT}}' => number_format($booking['total_amount'], 2),
            '{{PAYMENT_METHOD}}' => strtoupper($booking['payment_method'] ?? 'N/A'),
            '{{PAYMENT_REFERENCE}}' => $booking['payment_reference'] ?? 'N/A',
            '{{ADDITIONAL_FEES}}' => '' // Can add driver fee, delivery fee, etc.
        ];
        
        // Replace placeholders
        $html = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        // Generate PDF if TCPDF is available
        if ($hasTCPDF) {
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('CarGo System');
            $pdf->SetAuthor('CarGo Admin');
            $pdf->SetTitle('Receipt ' . $receiptNo);
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Write HTML
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Save to file
            $receiptDir = __DIR__ . '/../../receipts';
            if (!is_dir($receiptDir)) {
                mkdir($receiptDir, 0755, true);
            }
            
            $filename = "receipt_" . $bookingId . "_" . time() . ".pdf";
            $filepath = $receiptDir . "/" . $filename;
            
            $pdf->Output($filepath, 'F');
            
            // Update booking with receipt path
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET payment_reference = ?
                WHERE id = ?
            ");
            $receiptPath = "receipts/" . $filename;
            $stmt->bind_param("si", $receiptPath, $bookingId);
            $stmt->execute();
            
            return [
                'success' => true,
                'receipt_path' => $receiptPath,
                'receipt_url' => 'http://10.139.150.2/carGOAdmin/' . $receiptPath,
                'receipt_no' => $receiptNo
            ];
        } else {
            // Return HTML if no TCPDF
            return [
                'success' => true,
                'html' => $html,
                'receipt_no' => $receiptNo,
                'message' => 'TCPDF not installed. Returning HTML only.'
            ];
        }
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    } finally {
        if ($shouldClose) {
            $conn->close();
        }
    }
}

// API Endpoint
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    
    if (!isset($_GET['booking_id'])) {
        echo json_encode(['error' => 'Missing booking_id']);
        exit;
    }
    
    $bookingId = intval($_GET['booking_id']);
    $result = generateReceipt($bookingId);
    
    echo json_encode($result);
}