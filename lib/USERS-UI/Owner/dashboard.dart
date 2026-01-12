import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:intl/intl.dart';

// Services
import './dashboard/dashboard_service.dart';
import '../Owner/dashboard/booking_service.dart';

// Models
import './dashboard/dashboard_stats.dart';
import './dashboard/booking_model.dart';

// Widgets
import './dashboard/dashboard_header.dart';
import './dashboard/stat_card_widget.dart';
import './dashboard/revenue_overview_widget.dart';
import './dashboard/quick_action_card.dart';
import './dashboard/recent_activity_widget.dart';
import './dashboard/upcoming_bookings_widget.dart';

// Pages
import 'pending_requests_page.dart';
import 'active_booking_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> with SingleTickerProviderStateMixin {
  final DashboardService _dashboardService = DashboardService();
  final BookingService _bookingService = BookingService();

  String userName = "User";
  String ownerId = "0";
  
  DashboardStats stats = DashboardStats.empty();
  List<Booking> recentBookings = [];
  List<Booking> upcomingBookings = [];
  
  bool isLoading = true;

  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _loadData();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 1000),
      vsync: this,
    );
    
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOut),
    );
    
    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.2),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOutCubic),
    );
    
    _animationController.forward();
  }

  Future<void> _loadData() async {
    setState(() => isLoading = true);

    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      userName = prefs.getString("fullname") ?? "User";
      
      // Get owner ID
      ownerId = prefs.getString("user_id") ?? 
                prefs.getInt("user_id")?.toString() ?? 
                "0";

      // Fetch all data in parallel
      await Future.wait([
        _fetchDashboardStats(),
        _fetchRecentBookings(),
        _fetchUpcomingBookings(),
      ]);
    } catch (e) {
      debugPrint("Error loading dashboard data: $e");
    }

    setState(() => isLoading = false);
  }

  Future<void> _fetchDashboardStats() async {
    final fetchedStats = await _dashboardService.fetchDashboardStats(ownerId);
    setState(() => stats = fetchedStats);
  }

  Future<void> _fetchRecentBookings() async {
    final bookings = await _bookingService.fetchRecentBookings(ownerId, limit: 5);
    setState(() => recentBookings = bookings);
  }

  Future<void> _fetchUpcomingBookings() async {
    final bookings = await _bookingService.fetchUpcomingBookings(ownerId);
    setState(() => upcomingBookings = bookings);
  }

  String _formatCurrency(double amount) {
    final formatter = NumberFormat.currency(symbol: '₱', decimalDigits: 0);
    return formatter.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      body: RefreshIndicator(
        onRefresh: _loadData,
        color: Colors.black,
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: SlideTransition(
            position: _slideAnimation,
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header (without notification icon)
                  DashboardHeader(userName: userName),
                  
                  if (isLoading)
                    _buildLoadingIndicator()
                  else ...[
                    const SizedBox(height: 24),
                    
                    // Quick Stats Grid (only Total Cars and Total Income)
                    _buildQuickStatsGrid(),
                    
                    const SizedBox(height: 24),
                    
                    // Revenue Overview
                    RevenueOverview(
                      totalIncome: stats.totalIncome,
                      monthlyIncome: stats.monthlyIncome,
                      weeklyIncome: stats.weeklyIncome,
                      todayIncome: stats.todayIncome,
                    ),
                    
                    const SizedBox(height: 24),
                    
                    // Quick Actions (has Pending Requests and Active Bookings)
                    _buildQuickActions(),
                    
                    const SizedBox(height: 24),
                    
                    // Upcoming Bookings
                    UpcomingBookingsWidget(
                      upcomingBookings: upcomingBookings,
                      onViewAll: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const ActiveBookingsPage(),
                          ),
                        );
                      },
                    ),
                    
                    const SizedBox(height: 24),
                    
                    // Recent Activity
                    RecentActivityWidget(recentBookings: recentBookings),
                    
                    const SizedBox(height: 80),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingIndicator() {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(48.0),
        child: CircularProgressIndicator(color: Colors.black),
      ),
    );
  }

  Widget _buildQuickStatsGrid() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: GridView.count(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        crossAxisCount: 2,
        crossAxisSpacing: 14,
        mainAxisSpacing: 14,
        childAspectRatio: 1.2,
        children: [
          StatCard(
            title: "Total Cars",
            value: "${stats.totalCars}",
            icon: Icons.directions_car_outlined,
            subtitle: "${stats.approvedCars} active",
          ),
          StatCard(
            title: "Total Income",
            value: _formatCurrency(stats.totalIncome),
            icon: Icons.account_balance_wallet_outlined,
            iconBackgroundColor: Colors.purple.shade50,
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            "Quick Actions",
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 16),
          QuickActionCard(
            title: "Pending Requests",
            subtitle: "Review and approve bookings",
            count: stats.pendingRequests,
            icon: Icons.pending_actions_outlined,
            backgroundColor: Colors.black,
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => PendingRequestsPage(ownerId: ownerId),
                ),
              );
            },
          ),
          const SizedBox(height: 14),
          QuickActionCard(
            title: "Active Bookings",
            subtitle: "Currently rented vehicles",
            count: stats.activeBookings,
            icon: Icons.event_available_outlined,
            backgroundColor: Colors.grey.shade800,
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ActiveBookingsPage(),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}