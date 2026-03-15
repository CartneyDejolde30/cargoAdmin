<?php
/**
 * CarGO Push Notification Service
 * Uses Firebase Cloud Messaging (Legacy API)
 *
 * HOW TO GET YOUR SERVER KEY:
 * 1. Go to https://console.firebase.google.com
 * 2. Select your project → Project Settings → Cloud Messaging
 * 3. Under "Cloud Messaging API (Legacy)", enable it and copy the Server Key
 */

define('FCM_SERVER_KEY', 'YOUR_FCM_SERVER_KEY_HERE');

/**
 * Send a push notification to a specific user by user ID.
 * Looks up their FCM token from the database.
 *
 * @param mysqli $conn   DB connection
 * @param int    $userId Target user's ID
 * @param string $title  Notification title
 * @param string $body   Notification body
 * @param array  $data   Optional key-value data payload (for deep linking)
 * @return bool  true if sent, false if no token or send failed
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

    return _sendFCMPush($row['fcm_token'], $title, $body, $data);
}

/**
 * Low-level FCM send via HTTP Legacy API.
 * Includes both notification (shown by OS) and data (for Flutter handler).
 */
function _sendFCMPush($token, $title, $body, $data = []) {
    $payload = [
        'to' => $token,
        'notification' => [
            'title'        => $title,
            'body'         => $body,
            'sound'        => 'default',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ],
        'data' => array_merge([
            'title'        => $title,
            'body'         => $body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ], $data),
        'priority' => 'high',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: key=' . FCM_SERVER_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $result !== false && $httpCode === 200;
}

// Keep the old function signature for backwards compatibility
function sendNotification($token, $title, $body) {
    _sendFCMPush($token, $title, $body);
}
