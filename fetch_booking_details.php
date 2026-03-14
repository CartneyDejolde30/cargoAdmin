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
    -- Vehicle info (car OR motorcycle)
    COALESCE(c.brand, m.brand) AS brand,
    COALESCE(c.model, m.model) AS model,
    COALESCE(c.car_year, m.motorcycle_year) AS vehicle_year,
    COALESCE(c.plate_number, m.plate_number) AS plate_number,
    COALESCE(c.transmission, m.transmission_type) AS transmission,
    c.fuel_type AS fuel_type,
    c.seat AS seats,
    m.engine_displacement AS engine_displacement,
    m.body_style AS motorcycle_style,
    
    u1.fullname AS renter_name, u1.email AS renter_email, u1.phone AS renter_contact,
    u2.fullname AS owner_name, u2.email AS owner_email, u2.phone AS owner_contact,
    p.payment_status, p.id AS payment_id
FROM bookings b
LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
LEFT JOIN users u1 ON b.user_id = u1.id
LEFT JOIN users u2 ON b.owner_id = u2.id
LEFT JOIN payments p ON b.id = p.booking_id
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

// Determine payment status
$paymentStatus = $data['payment_status'] ?? 'unpaid';
$paymentClass = "unpaid";
$paymentLabel = "Unpaid";

if ($paymentStatus === 'verified' || $paymentStatus === 'paid') {
    $paymentClass = "paid";
    $paymentLabel = "Paid";
} elseif ($paymentStatus === 'pending') {
    $paymentClass = "pending";
    $paymentLabel = "Pending";
} elseif ($paymentStatus === 'refunded') {
    $paymentClass = "refunded";
    $paymentLabel = "Refunded";
}
?>

<!-- MODAL HTML OUTPUT STARTS HERE -->
<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-calendar-check"></i> Booking Details - <?= $bookingId ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <!-- Booking Information -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Booking Information</h6>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Booking ID:</span>
                <span class="info-value"><?= $bookingId ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Booking Date:</span>
                <span class="info-value"><?= date("F d, Y - h:i A", strtotime($data['created_at'])) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="badge bg-<?= $data['status'] === 'confirmed' ? 'success' : ($data['status'] === 'pending' ? 'warning' : ($data['status'] === 'cancelled' ? 'danger' : 'info')) ?>">
                        <?= ucfirst($data['status']) ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Payment Status:</span>
                <span class="info-value">
                    <span class="badge bg-<?= $paymentClass === 'paid' ? 'success' : ($paymentClass === 'pending' ? 'warning' : ($paymentClass === 'refunded' ? 'info' : 'secondary')) ?>">
                        <?= $paymentLabel ?>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <!-- Renter Information -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-user"></i> Renter Information</h6>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?= $data['renter_name'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= $data['renter_email'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone Number:</span>
                <span class="info-value"><?= $data['renter_contact'] ?></span>
            </div>
        </div>
    </div>

    <!-- Owner Information -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-user-tie"></i> Car Owner Information</h6>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?= $data['owner_name'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= $data['owner_email'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone Number:</span>
                <span class="info-value"><?= $data['owner_contact'] ?></span>
            </div>
        </div>
    </div>

    <!-- Vehicle Information -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-car"></i> Vehicle Information</h6>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Vehicle:</span>
                <span class="info-value"><?= $data['brand'] . " " . $data['model'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Year:</span>
                <span class="info-value"><?= htmlspecialchars($data['vehicle_year'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Plate Number:</span>
                <span class="info-value"><?= $data['plate_number'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Transmission:</span>
                <span class="info-value"><?= htmlspecialchars($data['transmission'] ?? 'N/A') ?></span>
            </div>
            <?php if (!empty($data['fuel_type'])): ?>
            <div class="info-item">
                <span class="info-label">Fuel Type:</span>
                <span class="info-value"><?= htmlspecialchars($data['fuel_type']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($data['seats'])): ?>
            <div class="info-item">
                <span class="info-label">Seating Capacity:</span>
                <span class="info-value"><?= intval($data['seats']) ?> persons</span>
            </div>
            <?php elseif (!empty($data['engine_displacement'])): ?>
            <div class="info-item">
                <span class="info-label">Engine Size:</span>
                <span class="info-value"><?= htmlspecialchars($data['engine_displacement']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rental Details -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt"></i> Rental Details</h6>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Pickup:</span>
                <span class="info-value">
<?php
if (!empty($data['pickup_date']) && $data['pickup_date'] !== '0000-00-00') {
    echo date("F d, Y", strtotime($data['pickup_date'])) . " — " . $data['pickup_time'];
} else {
    echo "Not set";
}
?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Drop-off:</span>
                <span class="info-value">
                    <?= date("F d, Y", strtotime($data['return_date'])) ?> — <?= $data['return_time'] ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Rental Duration:</span>
                <span class="info-value"><?= $data['rental_period'] ?></span>
            </div>
        </div>
    </div>

    <!-- Payment Breakdown -->
    <div class="mb-4">
        <h6 class="text-primary mb-3"><i class="fas fa-calculator"></i> Payment Breakdown</h6>
        <?php
        $bDays    = max(1, (int)((strtotime($data['return_date']) - strtotime($data['pickup_date'])) / 86400) + 1);
        $bPpd     = floatval($data['price_per_day'] ?? 0);
        $bBase    = round($bPpd * $bDays, 2);
        $bIns     = floatval($data['insurance_premium'] ?? 0);
        $bPeriod  = $data['rental_period'] ?? 'Day';
        $bDisc    = 0.0;
        if ($bPeriod === 'Weekly' && $bDays >= 7)       $bDisc = $bBase * 0.12;
        elseif ($bPeriod === 'Monthly' && $bDays >= 30) $bDisc = $bBase * 0.25;
        $bDiscounted = $bBase - $bDisc;
        $bSvcFee  = round(($bDiscounted + $bIns) * 0.05, 2);
        $bTotal   = floatval($data['total_amount']);
        $bSecDep  = floatval($data['security_deposit_amount'] ?? 0);
        $bGrand   = round($bTotal + $bSecDep, 2);
        ?>
        <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
            <tbody>
                <tr><td class="text-muted">Base Rental (<?= $bDays ?> day<?= $bDays > 1 ? 's' : '' ?> × ₱<?= number_format($bPpd, 2) ?>)</td><td class="text-end">₱<?= number_format($bBase, 2) ?></td></tr>
                <?php if ($bDisc > 0): ?>
                <tr><td class="text-muted"><?= $bPeriod ?> Discount</td><td class="text-end text-success">-₱<?= number_format($bDisc, 2) ?></td></tr>
                <?php endif; ?>
                <?php if ($bIns > 0): ?>
                <tr><td class="text-muted">Insurance Premium</td><td class="text-end">₱<?= number_format($bIns, 2) ?></td></tr>
                <?php endif; ?>
                <tr><td class="text-muted">Service Fee (5%)</td><td class="text-end">₱<?= number_format($bSvcFee, 2) ?></td></tr>
                <tr class="border-top"><td><strong>Total Rental Amount</strong></td><td class="text-end"><strong>₱<?= number_format($bTotal, 2) ?></strong></td></tr>
                <?php if ($bSecDep > 0): ?>
                <tr><td class="text-muted text-warning">Security Deposit (20%)</td><td class="text-end text-warning">₱<?= number_format($bSecDep, 2) ?></td></tr>
                <?php endif; ?>
                <tr class="border-top"><td><strong class="text-success">Grand Total</strong></td><td class="text-end"><strong class="text-success fs-6">₱<?= number_format($bGrand, 2) ?></strong></td></tr>
            </tbody>
        </table>
    </div>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="fas fa-times"></i> Close
    </button>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Print Details
    </button>
</div>
