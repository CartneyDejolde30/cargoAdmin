import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'booking_card_widget.dart';
import 'booking_empty_state_widget.dart';
import 'booking_tabs_widget.dart';
import 'package:flutter_application_1/USERS-UI/Renter/widgets/bottom_nav_bar.dart';

import 'package:flutter_application_1/USERS-UI/Renter/models/booking.dart';
import 'package:flutter_application_1/USERS-UI/services/booking_service.dart';

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  int _currentTabIndex = 0;
  int _selectedNavIndex = 1;

  // 🔄 Changed from late Future to nullable
  Future<List<Booking>>? _bookingFuture;
  
  // 🆕 User ID loaded from SharedPreferences
  String? userId;
  bool _isLoading = true;

  // =========================
  // LIFECYCLE
  // =========================
  @override
  void initState() {
    super.initState();

    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_onTabChanged);

    // 🆕 Load user ID first, then fetch bookings
    _loadUserIdAndFetchBookings();
  }

  // 🆕 LOAD USER ID FROM SHAREDPREFERENCES
  Future<void> _loadUserIdAndFetchBookings() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final loadedUserId = prefs.getString('user_id');

      if (loadedUserId == null || loadedUserId.isEmpty) {
        // User not logged in - show error or redirect to login
        if (mounted) {
          setState(() {
            _isLoading = false;
          });
          _showLoginRequiredDialog();
        }
        return;
      }

      // User ID found - fetch bookings
      if (mounted) {
        setState(() {
          userId = loadedUserId;
          _bookingFuture = BookingService.getMyBookings(int.parse(userId!));
          _isLoading = false;
        });
      }
    } catch (e) {
      print('❌ Error loading user ID: $e');
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  // 🆕 SHOW LOGIN REQUIRED DIALOG
  void _showLoginRequiredDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Text('Login Required'),
        content: Text('Please log in to view your bookings.'),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context); // Close dialog
              Navigator.pop(context); // Go back
            },
            child: Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (_currentTabIndex != _tabController.index) {
      setState(() {
        _currentTabIndex = _tabController.index;
      });
    }
  }

  // =========================
  // FILTERING LOGIC
  // =========================
  List<Booking> _filterBookings(List<Booking> all) {
    final now = DateTime.now();

    switch (_currentTabIndex) {
      case 0: // Active (approved + started)
        return all.where((b) {
          if (b.status != 'approved') return false;
          final pickup = _parseDate(b.pickupDate);
          return pickup != null && !pickup.isAfter(now);
        }).toList();

      case 1: // Pending
        return all.where((b) => b.status == 'pending').toList();

      case 2: // Upcoming (approved but future)
        return all.where((b) {
          if (b.status != 'approved') return false;
          final pickup = _parseDate(b.pickupDate);
          return pickup != null && pickup.isAfter(now);
        }).toList();

      case 3: // Past
        return all.where((b) =>
            b.status == 'completed' ||
            b.status == 'cancelled' ||
            b.status == 'rejected').toList();

      default:
        return [];
    }
  }

  // 🆕 HELPER TO PARSE DATE STRING
  DateTime? _parseDate(String dateStr) {
    try {
      // Handle formats like "Jan 05, 2026" or "2026-01-05"
      if (dateStr.contains('-')) {
        return DateTime.parse(dateStr);
      } else {
        // Parse "Jan 05, 2026" format
        final parts = dateStr.split(' ');
        if (parts.length >= 3) {
          final monthMap = {
            'Jan': 1, 'Feb': 2, 'Mar': 3, 'Apr': 4,
            'May': 5, 'Jun': 6, 'Jul': 7, 'Aug': 8,
            'Sep': 9, 'Oct': 10, 'Nov': 11, 'Dec': 12,
          };
          final month = monthMap[parts[0]];
          final day = int.tryParse(parts[1].replaceAll(',', ''));
          final year = int.tryParse(parts[2]);
          
          if (month != null && day != null && year != null) {
            return DateTime(year, month, day);
          }
        }
      }
    } catch (e) {
      print('Error parsing date: $dateStr - $e');
    }
    return null;
  }

  String _mapStatusForUI(String dbStatus) {
    switch (dbStatus) {
      case 'approved':
        return 'active';
      case 'pending':
        return 'pending';
      case 'completed':
      case 'cancelled':
      case 'rejected':
        return 'past';
      default:
        return 'pending';
    }
  }

  // =========================
  // BOTTOM NAV
  // =========================
  void _handleNavigation(int index) {
    if (_selectedNavIndex != index) {
      setState(() => _selectedNavIndex = index);
    }
  }

  // =========================
  // UI
  // =========================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: _buildAppBar(),
      body: Column(
        children: [
          const SizedBox(height: 16),
          _buildTabBar(),
          const SizedBox(height: 16),
          Expanded(child: _buildBookingBody()),
        ],
      ),
      bottomNavigationBar: BottomNavBar(
        currentIndex: _selectedNavIndex,
        onTap: _handleNavigation,
      ),
    );
  }

  // =========================
  // WIDGETS
  // =========================
  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      automaticallyImplyLeading: false,
      title: Text(
        'My Bookings',
        style: GoogleFonts.poppins(
          color: Colors.black,
          fontSize: 22,
          fontWeight: FontWeight.w600,
        ),
      ),
      centerTitle: false,
    );
  }

  Widget _buildBookingBody() {
    // 🆕 Show loading while fetching user ID
    if (_isLoading) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(color: Colors.black),
            SizedBox(height: 16),
            Text(
              'Loading bookings...',
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }

    // 🆕 Show error if no user ID
    if (userId == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
            SizedBox(height: 16),
            Text(
              'Not logged in',
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Please log in to view your bookings',
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }

    // 🆕 Now use the future
    return FutureBuilder<List<Booking>>(
      future: _bookingFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                SizedBox(height: 16),
                Text(
                  'Error loading bookings',
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                SizedBox(height: 8),
                Text(
                  snapshot.error.toString(),
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _bookingFuture = BookingService.getMyBookings(int.parse(userId!));

                    });
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                  ),
                  child: Text('Retry'),
                ),
              ],
            ),
          );
        }

        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return BookingEmptyStateWidget(
            onBrowseCars: () {
              Navigator.pushNamed(context, '/renters');
            },
          );
        }

        final bookings = _filterBookings(snapshot.data!);

        if (bookings.isEmpty) {
          return BookingEmptyStateWidget(
            onBrowseCars: () {
              Navigator.pushNamed(context, '/renters');
            },
          );
        }

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _bookingFuture = BookingService.getMyBookings(int.parse(userId!));

            });
          },
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
            itemCount: bookings.length,
            itemBuilder: (context, index) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: BookingCardWidget(
                  booking: bookings[index],
                  status: _mapStatusForUI(bookings[index].status),
                  onReviewSubmitted: () {
                    // Refresh bookings after review
                    setState(() {
                      _bookingFuture = BookingService.getMyBookings(int.parse(userId!));

                    });
                  },
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildTabBar() {
    return BookingTabsWidget(
      currentTabIndex: _currentTabIndex,
      onTabChanged: (index) {
        _tabController.animateTo(index);
      },
      // Badge counts can be wired later with real data
      badgeCounts: const [0, 0, 0, 0],
    );
  }
}