    <?php
    declare(strict_types=1);

    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);

    session_start();

    header('Content-Type: application/json');

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        require_once __DIR__ . '/../../include/db.php';
        require_once __DIR__ . '/transaction_logger.php';
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load required files: ' . $e->getMessage()]);
        exit;
    }

    /* =========================================================
    AUTH CHECK
    ========================================================= */
    if (empty($_SESSION['admin_id'])) {
        jsonError('Unauthorized access', 401);
    }

    $adminId = (int)$_SESSION['admin_id'];

    /* =========================================================
    METHOD CHECK
    ========================================================= */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Use POST method', 405);
    }

    /* =========================================================
    INPUT VALIDATION
    ========================================================= */
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $action    = trim($_POST['action'] ?? '');

    if ($paymentId <= 0 || !in_array($action, ['verify', 'reject'], true)) {
        jsonError('Missing or invalid fields');
    }

    /* =========================================================
    TRANSACTION
    ========================================================= */
    $conn->begin_transaction();

    try {
        $logger = new TransactionLogger($conn);

        /* =====================================================
        LOCK PAYMENT + BOOKING
        ===================================================== */
        $sql = "
        SELECT
            p.id                AS payment_id,
            p.payment_status  AS pay_status,
            p.amount,
            p.payment_method,
            p.payment_reference,
            b.id               AS booking_id,
            b.user_id,
            b.owner_id,
            u.fullname          AS renter_name,
            COALESCE(u.gcash_number, '') AS refund_account
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN users u ON u.id = b.user_id
        WHERE p.id = ?
        FOR UPDATE
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();

        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            throw new Exception('Payment not found');
        }

        $row = $res->fetch_assoc();

        if ($row['pay_status'] !== 'pending') {
            throw new Exception('Payment already processed');
        }

        $bookingId = (int)$row['booking_id'];
        $amount    = (float)$row['amount'];

        /* =====================================================
        VERIFY PAYMENT
        ===================================================== */
        if ($action === 'verify') {

            // Check if this is a late fee payment by checking payment_transactions metadata
            $sql = "SELECT transaction_type, metadata FROM payment_transactions 
                    WHERE booking_id = ? AND transaction_type = 'payment'
                    ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $transactionResult = $stmt->get_result();
            $transaction = $transactionResult->fetch_assoc();
            
            $isLateFeePayment = false;
            $lateFeeAmount = 0;
            $rentalAmount = 0;
            
            if ($transaction && $transaction['metadata']) {
                $metadata = json_decode($transaction['metadata'], true);
                if (isset($metadata['payment_type']) && $metadata['payment_type'] === 'late_fee_payment') {
                    $isLateFeePayment = true;
                    $lateFeeAmount = (float)($metadata['late_fee_amount'] ?? 0);
                    $rentalAmount = (float)($metadata['rental_amount'] ?? 0);
                }
            }

            $platformFee = round($amount * 0.10, 2);
            $ownerPayout = round($amount - $platformFee, 2);

            // Update payment
            $sql = "
            UPDATE payments
            SET payment_status = 'verified',
                verified_by = ?,
                verified_at = NOW()
            WHERE id = ?
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $adminId, $paymentId);
            $stmt->execute();

            // Insert escrow
            $sql = "
            INSERT INTO escrow (
                booking_id,
                payment_id,
                amount,
                status,
                held_at,
                processed_by
            ) VALUES (?, ?, ?, 'held', NOW(), ?)
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iidi", $bookingId, $paymentId, $amount, $adminId);
            $stmt->execute();

            $escrowId = $stmt->insert_id;

            // Update booking - mark late_fee_charged if this is a late fee payment
            if ($isLateFeePayment && $lateFeeAmount > 0) {
                $sql = "
                UPDATE bookings
                SET payment_status = 'paid',
                    escrow_status = 'held',
                    platform_fee = ?,
                    owner_payout = ?,
                    escrow_held_at = NOW(),
                    payment_verified_at = NOW(),
                    payment_verified_by = ?,
                    late_fee_charged = 1
                WHERE id = ?
                ";
            } else {
                $sql = "
                UPDATE bookings
                SET payment_status = 'paid',
                    escrow_status = 'held',
                    platform_fee = ?,
                    owner_payout = ?,
                    escrow_held_at = NOW(),
                    payment_verified_at = NOW(),
                    payment_verified_by = ?
                WHERE id = ?
                ";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ddii", $platformFee, $ownerPayout, $adminId, $bookingId);
            $stmt->execute();

            // Notify renter - different message for late fee payments
            if ($isLateFeePayment) {
                $sql = "
                INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
                VALUES (?, 'Late Fee Payment Verified ✓', CONCAT('Your late fee payment of ₱', ?, ' has been verified. Thank you for settling the overdue charges.'), 'payment_verified', 'unread', NOW())
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("id", $row['user_id'], $amount);
                $stmt->execute();
            } else {
                $sql = "
                INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
                VALUES (?, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'payment_verified', 'unread', NOW())
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $row['user_id']);
                $stmt->execute();
            }

            // Notify owner - different message for late fee payments
            if ($isLateFeePayment) {
                $sql = "
                INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
                VALUES (?, 'Late Fee Payment Received 💰', CONCAT('Booking #', ?, ' late fee payment of ₱', ?, ' has been received (Rental: ₱', ?, ' + Late Fee: ₱', ?, ').'), 'payment_received', 'unread', NOW())
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiddd", $row['owner_id'], $bookingId, $amount, $rentalAmount, $lateFeeAmount);
                $stmt->execute();
            } else {
                $sql = "
                INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
                VALUES (?, 'New Booking 🚗', CONCAT('Booking #', ?, ' has been confirmed. Payment received.'), 'booking_confirmed', 'unread', NOW())
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $row['owner_id'], $bookingId);
                $stmt->execute();
            }

            // Log actions with late fee details
            // NOTE: Only log for regular payments. Late fee payments are logged by verify_late_fee_payment.php
            if (!$isLateFeePayment) {
                $logMetadata = [
                    'payment_id' => $paymentId,
                    'escrow_id' => $escrowId,
                    'platform_fee' => $platformFee,
                    'owner_payout' => $ownerPayout
                ];
                
                $logger->log(
                    $bookingId,
                    'payment',
                    $amount,
                    "Payment verified via {$row['payment_method']}",
                    $adminId,
                    $logMetadata
                );
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'platform_fee' => $platformFee,
                    'owner_payout' => $ownerPayout
                ]
            ]);
            exit;
        }

        /* =====================================================
        REJECT PAYMENT
        ===================================================== */
        if ($action === 'reject') {

            $reason = trim($_POST['reason'] ?? 'Payment verification failed');

            // Update payment status
            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = 'rejected',
                    verified_by = ?,
                    verified_at = NOW(),
                    verification_notes = ?
                WHERE id = ?
            ");
            $stmt->bind_param("isi", $adminId, $reason, $paymentId);
            $stmt->execute();

            // Update booking status
            $stmt = $conn->prepare("
                UPDATE bookings
                SET payment_status = 'rejected',
                    status = 'rejected',
                    rejected_at = NOW(),
                    rejection_reason = ?,
                    refund_requested = 1,
                    refund_status = 'requested',
                    refund_amount = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sdi", $reason, $amount, $bookingId);
            $stmt->execute();

            // Auto-create refund record so admin can process it from the Refunds page
            $refundId = 'REF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $refundAccount  = $row['refund_account'] ?: '';
            $renterName     = $row['renter_name'] ?: '';
            $origMethod     = $row['payment_method'];
            $origRef        = $row['payment_reference'];
            $ownerId        = (int)$row['owner_id'];

            $stmt = $conn->prepare("
                INSERT INTO refunds (
                    refund_id, booking_id, payment_id, user_id, owner_id,
                    refund_amount, original_amount, deduction_amount,
                    refund_method, account_number, account_name,
                    refund_reason, reason_details, status,
                    original_payment_method, original_payment_reference,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'gcash', ?, ?, 'payment_rejected', ?, 'pending', ?, ?, NOW())
            ");
            $stmt->bind_param(
                "siiiiddsssss",
                $refundId,
                $bookingId,
                $paymentId,
                $row['user_id'],
                $ownerId,
                $amount,
                $amount,
                $refundAccount,
                $renterName,
                $reason,
                $origMethod,
                $origRef
            );
            $stmt->execute();

            // Notify renter — payment rejected + refund queued
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
                VALUES (?, 'Payment Rejected ✗',
                    CONCAT('Your payment of ₱', ?, ' was rejected. Reason: ', ?, '. A refund has been queued and will be processed shortly.'),
                    'payment_rejected', 'unread', NOW())
            ");
            $stmt->bind_param("ids", $row['user_id'], $amount, $reason);
            $stmt->execute();

            // Log rejection
            $logger->log(
                $bookingId,
                'payment',
                $amount,
                "Payment rejected: $reason — refund $refundId queued",
                $adminId,
                [
                    'payment_id' => $paymentId,
                    'reason'     => $reason,
                    'refund_id'  => $refundId
                ]
            );

            $conn->commit();

            echo json_encode([
                'success'   => true,
                'message'   => 'Payment rejected. Refund of ₱' . number_format($amount, 2) . ' has been queued for processing.',
                'refund_id' => $refundId
            ]);
            exit;
        }

        throw new Exception('Invalid action');

    } catch (Throwable $e) {
        $conn->rollback();
        jsonError('SQL ERROR: ' . $e->getMessage(), 500);
    }
