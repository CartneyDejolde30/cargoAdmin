import 'dart:convert';
import 'package:audioplayers/audioplayers.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/material.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:vibration/vibration.dart';
import 'package:http/http.dart' as http;
import 'package:google_fonts/google_fonts.dart';

import '../Renter/widgets/bottom_nav_bar.dart';

class NotificationScreen extends StatefulWidget {
  final int userId;
  const NotificationScreen({super.key, required this.userId});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  int _selectedNavIndex = 2;

  bool _isLoading = true;
  List<Map<String, dynamic>> _notifications = [];
  Map<String, List<Map<String, dynamic>>> grouped = {};

  final AudioPlayer player = AudioPlayer();
  int? _loadedUserId;

  @override
  void initState() {
    super.initState();
    _loadUserId();
    _listenToFCM();
  }

  // ---------------- LOAD USER_ID ---------------- //

  Future<void> _loadUserId() async {
    final prefs = await SharedPreferences.getInstance();

    // Try SharedPreferences first
    final savedId = prefs.getString("user_id");

    if (savedId != null) {
      _loadedUserId = int.tryParse(savedId);
    }

    // Fallback to widget.userId
    _loadedUserId ??= widget.userId;

    print("🔥 Loaded user_id: $_loadedUserId");

    _loadInitialNotifications();
  }

  // ---------------- LOAD NOTIFICATIONS ---------------- //

  Future<void> _loadInitialNotifications() async {
    if (_loadedUserId == null) {
      print("❌ user_id is NULL");
      setState(() => _isLoading = false);
      return;
    }

    try {
      final res = await http.get(
        Uri.parse(
            "http://10.139.150.2/carGOAdmin/get_notification_renter.php?user_id=$_loadedUserId"),
      );

      print("📩 RAW RESPONSE: ${res.body}");

      final decoded = jsonDecode(res.body);

      if (decoded is! Map || decoded["status"] != "success") {
        setState(() {
          _notifications = [];
          grouped.clear();
          _isLoading = false;
        });
        return;
      }

      final rawList = decoded["notifications"];
      final List<Map<String, dynamic>> safeList =
          (rawList is List) ? List<Map<String, dynamic>>.from(rawList) : [];

      setState(() {
        _notifications = safeList;
        _groupByDate();
        _isLoading = false;
      });
    } catch (e) {
      print("❌ ERROR: $e");

      setState(() {
        _notifications = [];
        grouped.clear();
        _isLoading = false;
      });
    }
  }

  // ---------------- REALTIME FCM LISTENER ---------------- //

  void _listenToFCM() {
    FirebaseMessaging.onMessage.listen((message) {
      _playSound();
      _vibrate();

      Map<String, dynamic> newNotif = {
        "id": message.data["id"],
        "title": message.notification?.title ?? "Notification",
        "message": message.notification?.body ?? "",
        "date": message.data["date"] ?? "Today",
        "time": message.data["time"] ?? "",
        "type": message.data["type"] ?? "info",
        "isRead": false
      };

      setState(() {
        _notifications.insert(0, newNotif);
        _groupByDate();
      });
    });
  }

  // ---------------- GROUP BY DATE ---------------- //

  void _groupByDate() {
    grouped.clear();

    for (var n in _notifications) {
      String date = n["date"] ?? "Unknown";

      if (!grouped.containsKey(date)) {
        grouped[date] = [];
      }

      grouped[date]!.add(n);
    }
  }

  // ---------------- SOUND + VIBRATE ---------------- //

  Future<void> _playSound() async {
    await player.play(AssetSource("notification_sound.mp3"));
  }

  void _vibrate() async {
    final hasVibrator = await Vibration.hasVibrator();
    if (hasVibrator == true) {
      Vibration.vibrate(duration: 80);
    }
  }

  // ---------------- GET ICON BY TYPE ---------------- //

  IconData _getIconByType(String type) {
    switch (type.toLowerCase()) {
      case 'booking':
        return Icons.calendar_month_outlined;
      case 'payment':
        return Icons.account_balance_wallet_outlined;
      case 'alert':
        return Icons.info_outline;
      case 'success':
        return Icons.verified_outlined;
      case 'message':
        return Icons.chat_bubble_outline_rounded;
      case 'info':
        return Icons.info_outline;
      default:
        return Icons.notifications_outlined;
    }
  }

  Color _getColorByType(String type) {
    // All icons are black for modern minimalist style
    return Colors.black;
  }

  Color _getBackgroundByType(String type) {
    // Subtle grey backgrounds for different notification types
    return Colors.grey.shade50;
  }

  // ---------------- MARK AS READ ---------------- //

  void _markAsRead(Map<String, dynamic> notification) {
    setState(() {
      notification["isRead"] = true;
    });
  }

  // ---------------- UI ---------------- //

  @override
  Widget build(BuildContext context) {
    final unreadCount = _notifications.where((n) => !n["isRead"]).length;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: Text(
          "Notifications",
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          if (_notifications.isNotEmpty && unreadCount > 0)
            TextButton(
              onPressed: () {
                setState(() {
                  for (var n in _notifications) {
                    n["isRead"] = true;
                  }
                });
              },
              child: Text(
                "Mark all read",
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Colors.black))
          : grouped.isEmpty
              ? _buildEmptyState()
              : _buildNotificationsList(),
      bottomNavigationBar: BottomNavBar(
        currentIndex: _selectedNavIndex,
        onTap: _handleNavigation,
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: Icon(
              Icons.notifications_none,
              size: 64,
              color: Colors.grey.shade400,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            "No notifications yet",
            style: GoogleFonts.poppins(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: Colors.black,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            "You're all caught up!",
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationsList() {
    return RefreshIndicator(
      color: Colors.black,
      onRefresh: _loadInitialNotifications,
      child: ListView.builder(
        padding: const EdgeInsets.all(20),
        itemCount: grouped.length,
        itemBuilder: (context, index) {
          String date = grouped.keys.elementAt(index);
          List<Map<String, dynamic>> list = grouped[date]!;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (index > 0) const SizedBox(height: 24),
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  date,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey.shade700,
                  ),
                ),
              ),
              ...list.map((n) => _buildNotificationItem(n)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildNotificationItem(Map<String, dynamic> n) {
    final type = n["type"] ?? "info";
    final iconColor = _getColorByType(type);
    final backgroundColor = _getBackgroundByType(type);
    final icon = _getIconByType(type);
    final isRead = n["isRead"] ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isRead ? Colors.grey.shade200 : Colors.black.withOpacity(0.2),
          width: isRead ? 1 : 1.5,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _markAsRead(n),
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: backgroundColor,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: Colors.grey.shade200,
                      width: 1,
                    ),
                  ),
                  child: Center(
                    child: Icon(
                      icon,
                      color: iconColor,
                      size: 24,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              n["title"],
                              style: GoogleFonts.poppins(
                                fontWeight: FontWeight.w600,
                                fontSize: 15,
                                color: Colors.black,
                              ),
                            ),
                          ),
                          if (!isRead)
                            Container(
                              width: 8,
                              height: 8,
                              margin: const EdgeInsets.only(left: 8),
                              decoration: const BoxDecoration(
                                color: Colors.black,
                                shape: BoxShape.circle,
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        n["message"],
                        style: GoogleFonts.poppins(
                          color: Colors.grey.shade600,
                          fontSize: 13,
                          height: 1.5,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(
                            Icons.access_time,
                            size: 14,
                            color: Colors.grey.shade400,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            n["time"],
                            style: GoogleFonts.poppins(
                              color: Colors.grey.shade500,
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _handleNavigation(int index) {}
}