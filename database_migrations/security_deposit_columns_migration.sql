-- ============================================================================
-- MIGRATION: Add missing security_deposit_* columns to bookings table
-- Safe to run multiple times (uses ADD COLUMN IF NOT EXISTS)
-- Run this on both local and production if these columns are missing
-- ============================================================================

ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `security_deposit_amount`
      DECIMAL(10,2) NOT NULL DEFAULT 0.00
      COMMENT 'Security deposit collected from renter at booking time'
      AFTER `insurance_verified`,

  ADD COLUMN IF NOT EXISTS `security_deposit_status`
      ENUM('pending','held','refunded','forfeited','partial_refund')
      NOT NULL DEFAULT 'pending'
      COMMENT 'Current state of the security deposit'
      AFTER `security_deposit_amount`,

  ADD COLUMN IF NOT EXISTS `security_deposit_held_at`
      DATETIME DEFAULT NULL
      COMMENT 'Timestamp when deposit was placed on hold (payment submitted)'
      AFTER `security_deposit_status`,

  ADD COLUMN IF NOT EXISTS `security_deposit_refunded_at`
      DATETIME DEFAULT NULL
      COMMENT 'Timestamp when deposit was refunded to renter'
      AFTER `security_deposit_held_at`,

  ADD COLUMN IF NOT EXISTS `security_deposit_refund_amount`
      DECIMAL(10,2) DEFAULT 0.00
      COMMENT 'Actual amount refunded (after deductions)'
      AFTER `security_deposit_refunded_at`,

  ADD COLUMN IF NOT EXISTS `security_deposit_deductions`
      DECIMAL(10,2) NOT NULL DEFAULT 0.00
      COMMENT 'Total deductions applied from security deposit (damage, late fees, etc.)'
      AFTER `security_deposit_refund_amount`,

  ADD COLUMN IF NOT EXISTS `security_deposit_deduction_reason`
      TEXT DEFAULT NULL
      COMMENT 'Reason for deposit deductions'
      AFTER `security_deposit_deductions`,

  ADD COLUMN IF NOT EXISTS `security_deposit_refund_reference`
      VARCHAR(100) DEFAULT NULL
      COMMENT 'Reference number for the deposit refund transaction'
      AFTER `security_deposit_deduction_reason`;

-- ============================================================================
-- MIGRATION: Verify payments table has payment_reference column
-- Already exists in schema but included here for completeness
-- ============================================================================

ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `payment_reference`
      VARCHAR(100) DEFAULT NULL
      COMMENT 'GCash or payment gateway reference number'
      AFTER `payment_method`;

-- ============================================================================
-- INDEX: Add index on payments.payment_reference for fast uniqueness checks
-- ============================================================================

ALTER TABLE `payments`
  ADD INDEX IF NOT EXISTS `idx_payment_reference` (`payment_reference`);

-- ============================================================================
-- INDEX: Add index on bookings security_deposit columns for fast lookups
-- ============================================================================

ALTER TABLE `bookings`
  ADD INDEX IF NOT EXISTS `idx_security_deposit_status` (`security_deposit_status`);

-- ============================================================================
-- VERIFICATION QUERY (run after migration to confirm)
-- ============================================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME = 'bookings'
--   AND COLUMN_NAME LIKE 'security_deposit%'
-- ORDER BY ORDINAL_POSITION;
