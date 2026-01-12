import 'package:flutter/material.dart';

class NotificationModel {
  final int id;
  final String title;
  final String message;
  final String createdAt;
  final String readStatus; // "read" or "unread"

  NotificationModel({
    required this.id,
    required this.title,
    required this.message,
    required this.createdAt,
    required this.readStatus,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      title: json['title']?.toString() ?? 'Notification',
      message: json['message']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
      readStatus: json['read_status']?.toString() ?? 'unread',
    );
  }

  bool get isUnread => readStatus.toLowerCase() == 'unread';

  // Get icon based on title
  IconData get icon {
    final lowerTitle = title.toLowerCase();
    
    if (lowerTitle.contains('booking') || lowerTitle.contains('request')) {
      return Icons.bookmark_outline;
    } else if (lowerTitle.contains('payment') || lowerTitle.contains('paid')) {
      return Icons.payment_outlined;
    } else if (lowerTitle.contains('confirm')) {
      return Icons.check_circle_outline;
    } else if (lowerTitle.contains('rental') || lowerTitle.contains('end')) {
      return Icons.event_available_outlined;
    } else if (lowerTitle.contains('cancel')) {
      return Icons.cancel_outlined;
    } else {
      return Icons.notifications_outlined;
    }
  }

  // Get color based on title
  Color get color {
    final lowerTitle = title.toLowerCase();
    
    if (lowerTitle.contains('booking') || lowerTitle.contains('request')) {
      return Colors.blue;
    } else if (lowerTitle.contains('payment')) {
      return Colors.green;
    } else if (lowerTitle.contains('confirm')) {
      return Colors.teal;
    } else if (lowerTitle.contains('cancel')) {
      return Colors.red;
    } else if (lowerTitle.contains('rental')) {
      return Colors.orange;
    } else {
      return Colors.purple;
    }
  }

  // Format time (HH:mm)
  String get formattedTime {
    try {
      return createdAt.substring(11, 16);
    } catch (e) {
      return '';
    }
  }
}