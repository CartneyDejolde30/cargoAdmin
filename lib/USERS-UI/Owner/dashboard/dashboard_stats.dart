class DashboardStats {
  final int totalCars;
  final int approvedCars;
  final int pendingCars;
  final int rentedCars;
  final int totalBookings;
  final int pendingRequests;
  final int activeBookings;
  final double totalIncome;
  final double monthlyIncome;
  final double weeklyIncome;
  final double todayIncome;
  final int unreadNotifications;
  final int unreadMessages;

  DashboardStats({
    required this.totalCars,
    required this.approvedCars,
    required this.pendingCars,
    required this.rentedCars,
    required this.totalBookings,
    required this.pendingRequests,
    required this.activeBookings,
    required this.totalIncome,
    required this.monthlyIncome,
    required this.weeklyIncome,
    required this.todayIncome,
    required this.unreadNotifications,
    required this.unreadMessages,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      totalCars: int.tryParse(json['total_cars']?.toString() ?? '0') ?? 0,
      approvedCars: int.tryParse(json['approved_cars']?.toString() ?? '0') ?? 0,
      pendingCars: int.tryParse(json['pending_cars']?.toString() ?? '0') ?? 0,
      rentedCars: int.tryParse(json['rented_cars']?.toString() ?? '0') ?? 0,
      totalBookings: int.tryParse(json['total_bookings']?.toString() ?? '0') ?? 0,
      pendingRequests: int.tryParse(json['pending_requests']?.toString() ?? '0') ?? 0,
      activeBookings: int.tryParse(json['active_bookings']?.toString() ?? '0') ?? 0,
      totalIncome: double.tryParse(json['total_income']?.toString() ?? '0') ?? 0.0,
      monthlyIncome: double.tryParse(json['monthly_income']?.toString() ?? '0') ?? 0.0,
      weeklyIncome: double.tryParse(json['weekly_income']?.toString() ?? '0') ?? 0.0,
      todayIncome: double.tryParse(json['today_income']?.toString() ?? '0') ?? 0.0,
      unreadNotifications: int.tryParse(json['unread_notifications']?.toString() ?? '0') ?? 0,
      unreadMessages: int.tryParse(json['unread_messages']?.toString() ?? '0') ?? 0,
    );
  }

  // Helper method for default/empty state
  factory DashboardStats.empty() {
    return DashboardStats(
      totalCars: 0,
      approvedCars: 0,
      pendingCars: 0,
      rentedCars: 0,
      totalBookings: 0,
      pendingRequests: 0,
      activeBookings: 0,
      totalIncome: 0.0,
      monthlyIncome: 0.0,
      weeklyIncome: 0.0,
      todayIncome: 0.0,
      unreadNotifications: 0,
      unreadMessages: 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'total_cars': totalCars,
      'approved_cars': approvedCars,
      'pending_cars': pendingCars,
      'rented_cars': rentedCars,
      'total_bookings': totalBookings,
      'pending_requests': pendingRequests,
      'active_bookings': activeBookings,
      'total_income': totalIncome,
      'monthly_income': monthlyIncome,
      'weekly_income': weeklyIncome,
      'today_income': todayIncome,
      'unread_notifications': unreadNotifications,
      'unread_messages': unreadMessages,
    };
  }
}