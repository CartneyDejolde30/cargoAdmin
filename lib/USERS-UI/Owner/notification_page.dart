import 'dart:async';
import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/material.dart';
import 'package:vibration/vibration.dart';
import 'package:google_fonts/google_fonts.dart';

// Services
import '../Owner/notification/notification_service.dart';

// Models
import '../Owner/notification/notification_model.dart';

// Utils
import '../Owner/notification/notification_helper.dart';

// Widgets
import '../Owner/notification/notification_item_widget.dart';

class NotificationPage extends StatefulWidget {
  final int userId;
  const NotificationPage({super.key, required this.userId});

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> 
    with SingleTickerProviderStateMixin {
  final NotificationService _notificationService = NotificationService();
  final AudioPlayer _audioPlayer = AudioPlayer();

  List<NotificationModel> notifications = [];
  Map<String, List<NotificationModel>> groupedNotifications = {};
  
  bool isLoading = true;
  bool hasError = false;
  String errorMessage = '';
  int lastCount = 0;
  
  Timer? _refreshTimer;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _loadNotifications();
    _startAutoRefresh();
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _audioPlayer.dispose();
    _animationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );
    
    _fadeAnimation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOut,
    );
    
    _animationController.forward();
  }

  void _startAutoRefresh() {
    _refreshTimer = Timer.periodic(
      const Duration(seconds: 10),
      (_) => _loadNotifications(silent: true),
    );
  }

  /* ---------------- LOAD NOTIFICATIONS ---------------- */
  Future<void> _loadNotifications({bool silent = false}) async {
    if (!silent) {
      setState(() {
        isLoading = true;
        hasError = false;
      });
    }

    try {
      final fetchedNotifications = await _notificationService.fetchNotifications(widget.userId);

      // Detect new notifications
      if (fetchedNotifications.length > lastCount && silent && lastCount > 0) {
        await _playAlertSound();
        _vibrate();
      }

      lastCount = fetchedNotifications.length;

      setState(() {
        notifications = fetchedNotifications;
        groupedNotifications = NotificationHelper.groupByDate(fetchedNotifications);
        isLoading = false;
        hasError = false;
      });
    } catch (e) {
      debugPrint("❌ Error loading notifications: $e");
      
      if (!silent) {
        setState(() {
          isLoading = false;
          hasError = true;
          errorMessage = NotificationHelper.getErrorMessage(e);
        });
      }
    }
  }

  /* ---------------- PLAY SOUND ---------------- */
  Future<void> _playAlertSound() async {
    try {
      await _audioPlayer.play(AssetSource("notification_sound.mp3"));
    } catch (e) {
      debugPrint("❌ Error playing sound: $e");
    }
  }

  /* ---------------- VIBRATE ---------------- */
  void _vibrate() async {
    try {
      if (await Vibration.hasVibrator() ?? false) {
        Vibration.vibrate(duration: 80);
      }
    } catch (e) {
      debugPrint("❌ Error vibrating: $e");
    }
  }

  /* ---------------- ARCHIVE NOTIFICATION ---------------- */
  Future<void> _archiveNotification(int notificationId) async {
    final success = await _notificationService.archiveNotification(notificationId);

    if (success) {
      setState(() {
        notifications.removeWhere((n) => n.id == notificationId);
        groupedNotifications = NotificationHelper.groupByDate(notifications);
      });

      if (mounted) {
        _showSnackBar(
          icon: Icons.archive_outlined,
          message: 'Notification archived',
          backgroundColor: Colors.orange.shade700,
        );
      }
    } else {
      if (mounted) {
        _showSnackBar(
          icon: Icons.error_outline,
          message: 'Failed to archive notification',
          backgroundColor: Colors.red.shade600,
        );
      }
    }
  }

  /* ---------------- MARK ALL AS READ ---------------- */
  Future<void> _markAllAsRead() async {
    final success = await _notificationService.markAllAsRead(widget.userId);

    if (success) {
      _loadNotifications(silent: true);

      if (mounted) {
        _showSnackBar(
          icon: Icons.check_circle_outline,
          message: 'All notifications marked as read',
          backgroundColor: Colors.green.shade600,
        );
      }
    } else {
      if (mounted) {
        _showSnackBar(
          icon: Icons.error_outline,
          message: 'Failed to mark notifications as read',
          backgroundColor: Colors.red.shade600,
        );
      }
    }
  }

  /* ---------------- SHOW SNACKBAR ---------------- */
  void _showSnackBar({
    required IconData icon,
    required String message,
    required Color backgroundColor,
  }) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(icon, color: Colors.white, size: 20),
            const SizedBox(width: 12),
            Text(
              message,
              style: GoogleFonts.poppins(fontSize: 14),
            ),
          ],
        ),
        backgroundColor: backgroundColor,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  /* ---------------- BUILD UI ---------------- */
  @override
  Widget build(BuildContext context) {
    final unreadCount = notifications.where((n) => n.isUnread).length;

    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: _buildAppBar(unreadCount),
      body: _buildBody(),
    );
  }

  /* ---------------- APP BAR ---------------- */
  AppBar _buildAppBar(int unreadCount) {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.black, size: 20),
        onPressed: () => Navigator.pop(context),
      ),
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            "Notifications",
            style: GoogleFonts.poppins(
              color: Colors.black,
              fontWeight: FontWeight.bold,
              fontSize: 20,
              letterSpacing: -0.5,
            ),
          ),
          if (unreadCount > 0)
            Text(
              "$unreadCount unread",
              style: GoogleFonts.poppins(
                color: Colors.grey.shade600,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
        ],
      ),
      actions: [
        if (unreadCount > 0)
          Container(
            margin: const EdgeInsets.only(right: 16),
            child: TextButton.icon(
              onPressed: _markAllAsRead,
              icon: const Icon(Icons.done_all_rounded, size: 18),
              label: Text(
                "Mark all read",
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
              style: TextButton.styleFrom(
                foregroundColor: Colors.black,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
      ],
    );
  }

  /* ---------------- BODY ---------------- */
  Widget _buildBody() {
    if (isLoading) {
      return _buildLoadingState();
    }

    if (hasError) {
      return _buildErrorState();
    }

    if (groupedNotifications.isEmpty) {
      return _buildEmptyState();
    }

    return _buildNotificationList();
  }

  /* ---------------- LOADING STATE ---------------- */
  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(color: Colors.black),
          const SizedBox(height: 16),
          Text(
            "Loading notifications...",
            style: GoogleFonts.poppins(
              color: Colors.grey.shade600,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }

  /* ---------------- ERROR STATE ---------------- */
  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.cloud_off_outlined,
                size: 64,
                color: Colors.red.shade400,
              ),
            ),
            const SizedBox(height: 24),
            Text(
              "Connection Error",
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              errorMessage,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _loadNotifications,
              icon: const Icon(Icons.refresh_rounded),
              label: Text(
                'Try Again',
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.black,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /* ---------------- EMPTY STATE ---------------- */
  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.notifications_off_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            "No notifications yet",
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            "We'll notify you when something arrives",
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  /* ---------------- NOTIFICATION LIST ---------------- */
  Widget _buildNotificationList() {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: RefreshIndicator(
        onRefresh: _loadNotifications,
        color: Colors.black,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: groupedNotifications.entries.map((section) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildSectionHeader(section.key),
                ...section.value.map((notification) {
                  return NotificationItemWidget(
                    notification: notification,
                    onArchive: () => _archiveNotification(notification.id),
                  );
                }),
                const SizedBox(height: 16),
              ],
            );
          }).toList(),
        ),
      ),
    );
  }

  /* ---------------- SECTION HEADER ---------------- */
  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 12, top: 8),
      child: Text(
        title,
        style: GoogleFonts.poppins(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
          letterSpacing: -0.3,
        ),
      ),
    );
  }
}