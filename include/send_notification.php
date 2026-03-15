<?php
/**
 * CarGO Push Notification Service — FCM V1 API
 *
 * SETUP (one-time):
 * 1. Firebase Console → Project Settings → Service Accounts
 * 2. Click "Generate new private key" → download JSON
 * 3. Rename it to service_account.json
 * 4. Upload to: public_html/cargoAdmin/include/service_account.json
 */

define('FCM_PROJECT_ID', 'project-1-d2d61');
define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/service_account.json');

/**
 * Send a push notification to a specific user by user ID.
 * Looks up their FCM token from the database.
 */
function sendPushToUser($conn, $userId, $title, $body, $data = []) {
    $stmt = $conn->prepare(
        "SELECT fcm_token FROM users WHERE id = ? AND fcm_token IS NOT NULL AND fcm_token != '' LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['fcm_token'])) {
        return false;
    }

    return _sendFCMV1Push($row['fcm_token'], $title, $body, $data);
}

/**
 * Send via FCM HTTP V1 API using a service account JSON key.
 * Implements the OAuth 2.0 JWT flow without any external libraries.
 */
function _sendFCMV1Push($token, $title, $body, $data = []) {
    $accessToken = _getFCMAccessToken();
    if (!$accessToken) {
        error_log('FCM: Failed to get access token');
        return false;
    }

    $stringData = [];
    foreach ($data as $k => $v) {
        $stringData[(string)$k] = (string)$v;
    }

    $payload = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data'    => $stringData,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound'        => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ],
    ];

    $url = 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("FCM V1 error ($httpCode): $result");
        return false;
    }

    return true;
}

/**
 * Build a JWT and exchange it for a short-lived OAuth 2.0 access token.
 * No external libraries needed — uses openssl_sign with RS256.
 */
function _getFCMAccessToken() {
    if (!file_exists(FCM_SERVICE_ACCOUNT_PATH)) {
        error_log('FCM: service_account.json not found at ' . FCM_SERVICE_ACCOUNT_PATH);
        return null;
    }

    $sa = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_PATH), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) {
        error_log('FCM: Invalid service_account.json');
        return null;
    }

    $now = time();
    $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims  = base64_encode(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    // URL-safe base64 (no padding, replace +/ with -_)
    $header = rtrim(strtr($header, '+/', '-_'), '=');
    $claims = rtrim(strtr($claims, '+/', '-_'), '=');

    $sigInput = "$header.$claims";
    $signature = '';
    openssl_sign($sigInput, $signature, $sa['private_key'], 'SHA256');
    $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    $jwt = "$sigInput.$signature";

    // Exchange JWT for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    return $tokenData['access_token'] ?? null;
}

// Backwards compatibility shim
function sendNotification($token, $title, $body) {
    _sendFCMV1Push($token, $title, $body);
}
