import 'package:flutter/material.dart';

class StatusHelper {
  /* ---------------- COLOR BASED ON STATUS ---------------- */
  static Color getStatusColor(String status) {
    final normalized = status.trim().toLowerCase();

    if (normalized == "approved") return Colors.green;
    if (normalized == "pending") return Colors.orange;
    if (normalized == "rejected") return Colors.redAccent;
    if (normalized == "rented") return Colors.blueAccent;

    return Colors.grey;
  }

  /* ---------------- STATUS ICON ---------------- */
  static IconData getStatusIcon(String status) {
    final normalized = status.trim().toLowerCase();

    if (normalized == "approved") return Icons.check_circle_outline;
    if (normalized == "pending") return Icons.schedule_outlined;
    if (normalized == "rejected") return Icons.cancel_outlined;
    if (normalized == "rented") return Icons.key_outlined;

    return Icons.help_outline;
  }

  /* ---------------- ALL STATUS OPTIONS ---------------- */
  static List<String> get allStatuses => ["All", "Approved", "Pending", "Rejected", "Rented"];
}