<?php
require_once "include/db.php";

// Validate ID
if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    echo "<div style='padding:20px;'>Invalid booking ID</div>";
    exit;
}

$id = intval($_GET['id']);

// Query booking data
$sql = "
SELECT 
    b.*,
    c.brand, c.model, c.car_year, c.plate_number, c.transmission, c.fuel_type, c.seat AS seats,
    u1.fullname AS renter_name, u1.email AS renter_email, u1.phone AS renter_contact,
    u2.fullname AS owner_name, u2.email AS owner_email, u2.phone AS owner_contact
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u1 ON b.user_id = u1.id
LEFT JOIN users u2 ON b.owner_id = u2.id
WHERE b.id = $id
LIMIT 1
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    echo "SQL ERROR: " . mysqli_error($conn);
    exit;
}

$data = mysqli_fetch_assoc($res);

if (!$data) {
    echo "<div style='padding:20px;'>Booking not found</div>";
    exit;
}

$bookingId = "#BK-" . str_pad($data['id'], 4, "0", STR_PAD_LEFT);
?>

<!-- MODAL HTML OUTPUT STARTS HERE -->
<div class="modal-header">
    <h5 class="modal-title">Booking Details - <?= $bookingId ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <!-- Booking Information -->
    <div class="detail-section">
        <div class="detail-section-title">Booking Information</div>
        <div class="detail-row">

            <div class="detail-item">
                <span class="detail-label">Booking ID</span>
                <span class="detail-value"><?= $bookingId ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Booking Date</span>
                <span class="detail-value"><?= date("F d, Y - h:i A", strtotime($data['created_at'])) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="status-badge <?= $data['status'] ?>"><?= ucfirst($data['status']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Payment Status</span>
                <span class="payment-badge unpaid">Unpaid</span>
            </div>

        </div>
    </div>

    <!-- Renter Information -->
    <div class="detail-section">
        <div class="detail-section-title">Renter Information</div>
        <div class="detail-row">
            <div class="detail-item">
                <span class="detail-label">Full Name</span>
                <span class="detail-value"><?= $data['renter_name'] ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?= $data['renter_email'] ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Phone Number</span>
                <span class="detail-value"><?= $data['renter_contact'] ?></span>
            </div>
        </div>
    </div>

    <!-- Owner Information -->
    <div class="detail-section">
        <div class="detail-section-title">Car Owner Information</div>
        <div class="detail-row">
            <div class="detail-item">
                <span class="detail-label">Full Name</span>
                <span class="detail-value"><?= $data['owner_name'] ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?= $data['owner_email'] ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Phone Number</span>
                <span class="detail-value"><?= $data['owner_contact'] ?></span>
            </div>
        </div>
    </div>

    <!-- Vehicle Information -->
    <div class="detail-section">
        <div class="detail-section-title">Vehicle Information</div>
        <div class="detail-row">
            <div class="detail-item">
                <span class="detail-label">Vehicle</span>
                <span class="detail-value"><?= $data['brand'] . " " . $data['model'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Year</span>
                <span class="detail-value"><?= $data['car_year'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Plate Number</span>
                <span class="detail-value"><?= $data['plate_number'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Transmission</span>
                <span class="detail-value"><?= $data['transmission'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fuel Type</span>
                <span class="detail-value"><?= $data['fuel_type'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Seating Capacity</span>
                <span class="detail-value"><?= $data['seats'] ?> persons</span>
            </div>
        </div>
    </div>

    <!-- Rental Details -->
    <div class="detail-section">
        <div class="detail-section-title">Rental Details</div>
        <div class="detail-row">

            <div class="detail-item">
                <span class="detail-label">Pickup</span>
                <span class="detail-value">
                    <?= date("F d, Y", strtotime($data['pickup_date'])) ?> — <?= $data['pickup_time'] ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Drop-off</span>
                <span class="detail-value">
                    <?= date("F d, Y", strtotime($data['return_date'])) ?> — <?= $data['return_time'] ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Rental Duration</span>
                <span class="detail-value"><?= $data['rental_period'] ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Total Amount</span>
                <span class="detail-value">₱<?= number_format($data['total_amount'], 2) ?></span>
            </div>

        </div>
    </div>

</div>

<div class="modal-footer">
    <button class="modal-btn secondary" data-bs-dismiss="modal">Close</button>
    
</div>
