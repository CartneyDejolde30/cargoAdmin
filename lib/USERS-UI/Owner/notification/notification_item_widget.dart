import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import './notification_model.dart';

class NotificationItemWidget extends StatelessWidget {
  final NotificationModel notification;
  final VoidCallback onArchive;

  const NotificationItemWidget({
    super.key,
    required this.notification,
    required this.onArchive,
  });

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: Key(notification.id.toString()),
      direction: DismissDirection.endToStart,
      background: _buildArchiveBackground(),
      onDismissed: (_) => onArchive(),
      child: _buildNotificationCard(),
    );
  }

  Widget _buildArchiveBackground() {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: Colors.orange.shade700,
      ),
      alignment: Alignment.centerRight,
      padding: const EdgeInsets.only(right: 24),
      child: const Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.archive_outlined, color: Colors.white, size: 28),
          SizedBox(height: 4),
          Text(
            "Archive",
            style: TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationCard() {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: notification.isUnread ? Colors.white : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: notification.isUnread 
              ? notification.color.withValues(alpha: 0.3) 
              : Colors.grey.shade200,
          width: notification.isUnread ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: notification.isUnread 
                ? notification.color.withValues(alpha: 0.08)
                : Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildIcon(),
          const SizedBox(width: 14),
          Expanded(child: _buildContent()),
        ],
      ),
    );
  }

  Widget _buildIcon() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: notification.isUnread 
            ? notification.color.withValues(alpha: 0.1) 
            : Colors.grey.shade200,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(
        notification.icon,
        color: notification.isUnread 
            ? notification.color 
            : Colors.grey.shade600,
        size: 24,
      ),
    );
  }

  Widget _buildContent() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                notification.title,
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: notification.isUnread 
                      ? FontWeight.bold 
                      : FontWeight.w600,
                  color: Colors.black87,
                  letterSpacing: -0.2,
                ),
              ),
            ),
            if (notification.isUnread) _buildUnreadIndicator(),
          ],
        ),
        const SizedBox(height: 6),
        Text(
          notification.message,
          style: GoogleFonts.poppins(
            fontSize: 13,
            color: Colors.grey.shade700,
            height: 1.4,
          ),
        ),
        const SizedBox(height: 8),
        _buildTimeStamp(),
      ],
    );
  }

  Widget _buildUnreadIndicator() {
    return Container(
      width: 10,
      height: 10,
      decoration: BoxDecoration(
        color: notification.color,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: notification.color.withValues(alpha: 0.4),
            blurRadius: 4,
            spreadRadius: 1,
          ),
        ],
      ),
    );
  }

  Widget _buildTimeStamp() {
    return Row(
      children: [
        Icon(
          Icons.access_time_rounded,
          size: 14,
          color: Colors.grey.shade500,
        ),
        const SizedBox(width: 4),
        Text(
          notification.formattedTime,
          style: GoogleFonts.poppins(
            fontSize: 12,
            color: Colors.grey.shade500,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}