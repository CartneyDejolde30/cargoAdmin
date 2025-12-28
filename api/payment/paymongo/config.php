<?php
// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_hqGiPKfNstoVKaH7JkKFbXKJ'); // Get from dashboard.paymongo.com
define('PAYMONGO_PUBLIC_KEY', 'pk_test_L4oDta1CFrmWq9pjYCT9dgH1');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET');

function paymongoRequest($endpoint, $method = 'POST', $data = null) {
    $ch = curl_init(PAYMONGO_API_URL . $endpoint);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 400) {
        error_log("PayMongo Error: " . json_encode($result));
        return ['error' => true, 'message' => $result['errors'][0]['detail'] ?? 'API failed'];
    }
    
    return $result;
}