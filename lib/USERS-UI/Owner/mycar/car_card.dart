import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import './status_helper.dart';
import './api_constants.dart';

class CarCard extends StatelessWidget {
  final Map<String, dynamic> car;
  final VoidCallback onTap;
  final Function(String) onMenuSelected;

  const CarCard({
    super.key,
    required this.car,
    required this.onTap,
    required this.onMenuSelected,
  });

  @override
  Widget build(BuildContext context) {
    final imageUrl = ApiConstants.getCarImageUrl(car['image']);
    final status = car["status"] ?? "Unknown";

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Image with Preview
          _buildCarImage(context, imageUrl, status),

          // Car Details
          Expanded(
            child: InkWell(
              onTap: onTap,
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(18)),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "${car['brand']} ${car['model']}",
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: Colors.black87,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.payments_outlined, size: 14, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          "₱ ${car['price_per_day']}/day",
                          style: GoogleFonts.poppins(
                            color: Colors.grey.shade700,
                            fontWeight: FontWeight.w600,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),

                    // Menu Button
                    _buildMenuButton(context),
                  ],
                ),
              ),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildCarImage(BuildContext context, String imageUrl, String status) {
    return GestureDetector(
      onTap: () => _showImagePreview(context, imageUrl),
      child: Stack(
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
            child: Image.network(
              imageUrl,
              height: 110,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                height: 110,
                color: Colors.grey.shade200,
                child: Icon(
                  Icons.directions_car,
                  size: 40,
                  color: Colors.grey.shade400,
                ),
              ),
            ),
          ),
          Positioned(
            top: 10,
            left: 10,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: StatusHelper.getStatusColor(status),
                borderRadius: BorderRadius.circular(10),
                boxShadow: [
                  BoxShadow(
                    color: StatusHelper.getStatusColor(status).withValues(alpha: 0.3),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Text(
                status.toUpperCase(),
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.5,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuButton(BuildContext context) {
    return Align(
      alignment: Alignment.bottomRight,
      child: PopupMenuButton(
        icon: Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(
            Icons.more_horiz_rounded,
            size: 20,
            color: Colors.black87,
          ),
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        offset: const Offset(0, 10),
        onSelected: onMenuSelected,
        itemBuilder: (_) => [
          PopupMenuItem(
            value: "edit",
            child: Row(
              children: [
                Icon(Icons.edit_outlined, size: 20, color: Colors.grey.shade700),
                const SizedBox(width: 12),
                Text("Edit", style: GoogleFonts.poppins(fontSize: 14)),
              ],
            ),
          ),
          PopupMenuItem(
            value: "delete",
            child: Row(
              children: [
                const Icon(Icons.delete_outline_rounded, size: 20, color: Colors.red),
                const SizedBox(width: 12),
                Text(
                  "Delete",
                  style: GoogleFonts.poppins(fontSize: 14, color: Colors.red),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showImagePreview(BuildContext context, String imageUrl) {
    showDialog(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.transparent,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: Image.network(
            imageUrl,
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              height: 300,
              color: Colors.grey.shade200,
              child: Icon(
                Icons.directions_car,
                size: 64,
                color: Colors.grey.shade400,
              ),
            ),
          ),
        ),
      ),
    );
  }
}