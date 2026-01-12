import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class RevenueOverview extends StatelessWidget {
  final double totalIncome;
  final double monthlyIncome;
  final double weeklyIncome;
  final double todayIncome;

  const RevenueOverview({
    super.key,
    required this.totalIncome,
    required this.monthlyIncome,
    required this.weeklyIncome,
    required this.todayIncome,
  });

  String _formatCurrency(double amount) {
    final formatter = NumberFormat.currency(symbol: '₱', decimalDigits: 0);
    return formatter.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 24),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.grey.shade200, width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                "Revenue Overview",
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  letterSpacing: -0.5,
                ),
              ),
              Icon(Icons.trending_up, color: Colors.green.shade600, size: 24),
            ],
          ),
          const SizedBox(height: 24),
          _buildRevenueItem(
            label: "Total Income",
            amount: totalIncome,
            icon: Icons.account_balance_wallet_outlined,
            color: Colors.purple,
            isMain: true,
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildRevenueItem(
                  label: "This Month",
                  amount: monthlyIncome,
                  icon: Icons.calendar_month_outlined,
                  color: Colors.orange,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildRevenueItem(
                  label: "This Week",
                  amount: weeklyIncome,
                  icon: Icons.calendar_today_outlined,
                  color: Colors.blue,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _buildRevenueItem(
            label: "Today",
            amount: todayIncome,
            icon: Icons.access_time_outlined,
            color: Colors.green,
          ),
        ],
      ),
    );
  }

  Widget _buildRevenueItem({
    required String label,
    required double amount,
    required IconData icon,
    required Color color,
    bool isMain = false,
  }) {
    return Container(
      padding: EdgeInsets.all(isMain ? 16 : 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: color.withValues(alpha: 0.3),
          width: 1,
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: EdgeInsets.all(isMain ? 10 : 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: isMain ? 24 : 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: isMain ? 14 : 12,
                    color: Colors.grey.shade700,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _formatCurrency(amount),
                  style: TextStyle(
                    fontSize: isMain ? 24 : 18,
                    fontWeight: FontWeight.bold,
                    letterSpacing: -0.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}