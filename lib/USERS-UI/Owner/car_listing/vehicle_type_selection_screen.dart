import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'car_details.dart';

class VehicleTypeSelectionScreen extends StatelessWidget {
  final int ownerId;

  const VehicleTypeSelectionScreen({super.key, required this.ownerId});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'What would you like to list?',
                style: GoogleFonts.poppins(
                  fontSize: 28,
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Choose the type of vehicle you want to rent out',
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  color: Colors.black54,
                ),
              ),
              const SizedBox(height: 48),
              
              _buildVehicleOption(
                context,
                icon: Icons.directions_car,
                title: 'Car',
                subtitle: 'List your car for rent',
                onTap: () => _navigateToListing(context, 'car'),
              ),
              
              const SizedBox(height: 20),
              
              _buildVehicleOption(
                context,
                icon: Icons.two_wheeler,
                title: 'Motorcycle',
                subtitle: 'List your motorcycle for rent',
                onTap: () => _navigateToListing(context, 'motorcycle'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildVehicleOption(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: const Color(0xFFE0E0E0),
            width: 2,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.black,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: Colors.white, size: 32),
            ),
            const SizedBox(width: 20),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.poppins(
                      fontSize: 20,
                      fontWeight: FontWeight.w600,
                      color: Colors.black,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.black54,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, color: Colors.black54, size: 20),
          ],
        ),
      ),
    );
  }

  void _navigateToListing(BuildContext context, String vehicleType) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CarDetailsScreen(
          ownerId: ownerId,
          vehicleType: vehicleType,
        ),
      ),
    );
  }
}