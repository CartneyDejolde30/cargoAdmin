import 'package:intl/intl.dart';
import './notification_model.dart';

class NotificationHelper {
  /* ---------------- GROUP NOTIFICATIONS BY DATE ---------------- */
  static Map<String, List<NotificationModel>> groupByDate(List<NotificationModel> notifications) {
    final Map<String, List<NotificationModel>> grouped = {};

    final today = DateTime.now();
    final yesterday = today.subtract(const Duration(days: 1));

    for (var notification in notifications) {
      try {
        final date = DateTime.parse(notification.createdAt);
        final formatted = DateFormat("yyyy-MM-dd").format(date);

        String label = "Earlier";
        if (formatted == DateFormat("yyyy-MM-dd").format(today)) {
          label = "Today";
        } else if (formatted == DateFormat("yyyy-MM-dd").format(yesterday)) {
          label = "Yesterday";
        }

        grouped.putIfAbsent(label, () => []).add(notification);
      } catch (e) {
        // If date parsing fails, put in "Earlier"
        grouped.putIfAbsent("Earlier", () => []).add(notification);
      }
    }

    // Sort groups: Today -> Yesterday -> Earlier
    final sortedMap = <String, List<NotificationModel>>{};
    if (grouped.containsKey("Today")) {
      sortedMap["Today"] = grouped["Today"]!;
    }
    if (grouped.containsKey("Yesterday")) {
      sortedMap["Yesterday"] = grouped["Yesterday"]!;
    }
    if (grouped.containsKey("Earlier")) {
      sortedMap["Earlier"] = grouped["Earlier"]!;
    }

    return sortedMap;
  }

  /* ---------------- GET ERROR MESSAGE ---------------- */
  static String getErrorMessage(dynamic error) {
    if (error.toString().contains('TimeoutException')) {
      return 'Connection timeout. Please check your internet connection.';
    } else if (error.toString().contains('Failed host lookup') || 
               error.toString().contains('Failed to fetch') ||
               error.toString().contains('SocketException')) {
      return 'Cannot connect to server. Please check your network connection.';
    } else if (error.toString().contains('FormatException')) {
      return 'Invalid server response. Please try again later.';
    } else {
      return 'Unable to load notifications. Please try again.';
    }
  }
}