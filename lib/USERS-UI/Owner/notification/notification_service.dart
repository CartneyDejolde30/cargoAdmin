import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import '../../Owner/mycar/api_constants.dart';
import './notification_model.dart';

class NotificationService {
  /* ---------------- FETCH ALL NOTIFICATIONS ---------------- */
  Future<List<NotificationModel>> fetchNotifications(int userId) async {
    try {
      final url = Uri.parse("${ApiConstants.baseUrl}get_notification.php?user_id=$userId");
      final response = await http.get(url).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['notifications'] is List) {
          return (data['notifications'] as List)
              .map((json) => NotificationModel.fromJson(json))
              .toList();
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching notifications: $e");
      rethrow; // Re-throw to let UI handle error display
    }

    return [];
  }

  /* ---------------- FETCH UNREAD COUNT ---------------- */
  Future<Map<String, int>> fetchUnreadCounts(String userId) async {
    try {
      final url = Uri.parse("${ApiConstants.baseUrl}api/dashboard/unread_counts.php?user_id=$userId");
      final response = await http.get(url).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          return {
            'notifications': int.tryParse(data['unread_notifications']?.toString() ?? '0') ?? 0,
            'messages': int.tryParse(data['unread_messages']?.toString() ?? '0') ?? 0,
          };
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching unread counts: $e");
    }

    return {'notifications': 0, 'messages': 0};
  }

  /* ---------------- MARK NOTIFICATION AS READ ---------------- */
  Future<bool> markAsRead(String notificationId) async {
    try {
      final response = await http.post(
        Uri.parse("${ApiConstants.baseUrl}api/mark_notification_read.php"),
        body: {'notification_id': notificationId},
      ).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      }
    } catch (e) {
      debugPrint("❌ Error marking notification as read: $e");
    }

    return false;
  }

  /* ---------------- MARK ALL AS READ ---------------- */
  Future<bool> markAllAsRead(int userId) async {
    try {
      final response = await http.post(
        Uri.parse("${ApiConstants.baseUrl}update_all.php"),
        body: {'user_id': userId.toString()},
      ).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        return true;
      }
    } catch (e) {
      debugPrint("❌ Error marking all as read: $e");
    }

    return false;
  }

  /* ---------------- ARCHIVE NOTIFICATION ---------------- */
  Future<bool> archiveNotification(int notificationId) async {
    try {
      final response = await http.post(
        Uri.parse("${ApiConstants.baseUrl}archive_notification.php"),
        body: {'id': notificationId.toString()},
      ).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        return true;
      }
    } catch (e) {
      debugPrint("❌ Error archiving notification: $e");
    }

    return false;
  }
}