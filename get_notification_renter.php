<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once "include/db.php";

if (!isset($_GET["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit;
}

$user_id = intval($_GET["user_id"]);

$sql = "SELECT id, title, message, status, created_at 
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

function detectType($title) {
    $title = strtolower($title);

    if (str_contains($title, "approved")) return "booking_approved";
    if (str_contains($title, "rejected") || str_contains($title, "declined")) return "booking_declined";
    if (str_contains($title, "returned")) return "car_returned";
    if (str_contains($title, "message")) return "new_message";
    if (str_contains($title, "payment") && str_contains($title, "successful")) return "payment_success";
    if (str_contains($title, "payment") && str_contains($title, "failed")) return "payment_failed";
    if (str_contains($title, "refund")) return "refund_processed";
    if (str_contains($title, "announce")) return "system_announcement";
    if (str_contains($title, "pending")) return "verification_pending";
    if (str_contains($title, "reminder")) return "booking_reminder";

    return "info";
}

function iconForType($type) {
    return match($type) {
        "booking_approved"      => "✅",
        "booking_declined"      => "❌",
        "car_returned"          => "🚗",
        "new_message"           => "💬",
        "payment_success"       => "💳",
        "payment_failed"        => "⚠️",
        "refund_processed"      => "🔄",
        "system_announcement"   => "📣",
        "verification_pending"  => "🔔",
        "booking_reminder"      => "🕒",
        default                 => "ℹ️",
    };
}

while ($row = $result->fetch_assoc()) {

    $type = detectType($row["title"]);
    $emoji = iconForType($type);

    $notifications[] = [
        "id"        => $row["id"],
        "title"     => $emoji . " " . $row["title"],
        "message"   => $row["message"],
        "date"      => date("M d", strtotime($row["created_at"])),
        "time"      => date("h:i A", strtotime($row["created_at"])),
        "type"      => $type,
        "isRead"    => strtolower($row["status"]) === "read"
    ];
}

echo json_encode([
    "status" => "success",
    "notifications" => $notifications
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
