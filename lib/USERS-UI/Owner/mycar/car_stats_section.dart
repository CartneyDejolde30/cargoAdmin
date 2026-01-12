import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class CarStatsSection extends StatelessWidget {
  final List<Map<String, dynamic>> cars;

  const CarStatsSection({
    super.key,
    required this.cars,
  });

  int get totalCars => cars.length;
  int get approvedCars => cars.where((c) => c['status']?.toString().toLowerCase() == 'approved').length;
  int get pendingCars => cars.where((c) => c['status']?.toString().toLowerCase() == 'pending').length;
  int get rentedCars => cars.where((c) => c['status']?.toString().toLowerCase() == 'rented').length;

  @override
  Widget build(BuildContext context) {
    if (cars.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.black, Colors.grey.shade800],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildStatItem(
            icon: Icons.directions_car_outlined,
            label: "Total",
            value: totalCars.toString(),
            color: Colors.white,
          ),
          _buildDivider(),
          _buildStatItem(
            icon: Icons.check_circle_outline,
            label: "Approved",
            value: approvedCars.toString(),
            color: Colors.greenAccent,
          ),
          _buildDivider(),
          _buildStatItem(
            icon: Icons.schedule_outlined,
            label: "Pending",
            value: pendingCars.toString(),
            color: Colors.orangeAccent,
          ),
          _buildDivider(),
          _buildStatItem(
            icon: Icons.key_outlined,
            label: "Rented",
            value: rentedCars.toString(),
            color: Colors.blueAccent,
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 6),
        Text(
          value,
          style: GoogleFonts.poppins(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 11,
            color: Colors.white70,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildDivider() {
    return Container(
      width: 1,
      height: 50,
      color: Colors.white24,
    );
  }
}