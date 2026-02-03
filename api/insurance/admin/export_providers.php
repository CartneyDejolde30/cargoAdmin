<?php
/**
 * Export Insurance Providers to CSV
 */

require_once __DIR__ . '/../../../include/db.php';

try {
    $query = "
        SELECT 
            provider_name,
            contact_phone,
            contact_email,
            status,
            created_at,
            (SELECT COUNT(*) FROM insurance_policies WHERE provider_id = insurance_providers.id) as total_policies,
            (SELECT COUNT(*) FROM insurance_policies WHERE provider_id = insurance_providers.id AND status = 'active') as active_policies
        FROM insurance_providers
        ORDER BY provider_name ASC
    ";
    
    $result = $conn->query($query);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="insurance_providers_' . date('Y-m-d') . '.csv"');
    
    // Output CSV header
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Provider Name',
        'Contact Phone',
        'Contact Email',
        'Status',
        'Total Policies',
        'Active Policies',
        'Created Date'
    ]);
    
    // Output data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['provider_name'],
            $row['contact_phone'],
            $row['contact_email'],
            $row['status'],
            $row['total_policies'],
            $row['active_policies'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
