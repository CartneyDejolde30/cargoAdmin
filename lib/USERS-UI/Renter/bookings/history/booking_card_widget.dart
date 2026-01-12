import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'booking_detail_screen.dart';
import 'package:flutter_application_1/USERS-UI/Reporting/submit_review_screen.dart';
import 'package:flutter_application_1/USERS-UI/Renter/models/booking.dart';

class BookingCardWidget extends StatelessWidget {
  final Booking booking;
  final String status;
  final VoidCallback? onReviewSubmitted;

  const BookingCardWidget({
    super.key,
    required this.booking,
    required this.status,
    this.onReviewSubmitted,
  });

  String _getStatusText() {
    switch (status) {
      case 'active':
        return 'Active';
      case 'pending':
        return 'Pending Payment';
      case 'upcoming':
        return 'Confirmed';
      case 'past':
        return 'Completed';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha((0.04 * 255).round()),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          // =========================
          // CAR INFO
          // =========================
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 100,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: _buildCarImage(booking.carImage),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              booking.carName,
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.black,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          _statusBadge(),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Icon(Icons.location_on,
                              size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              booking.location,
                              style: GoogleFonts.poppins(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Booking ID: ${booking.bookingId}',
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          Divider(height: 1, color: Colors.grey.shade200),

          // =========================
          // RENTAL PERIOD
          // =========================
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: _buildDateInfo(
                    'Pick up',
                    booking.pickupDate,
                    booking.pickupTime,
                  ),
                ),
                SizedBox(
                  width: 40,
                  child: Icon(Icons.arrow_forward,
                      size: 20, color: Colors.grey.shade400),
                ),
                Expanded(
                  child: _buildDateInfo(
                    'Return',
                    booking.returnDate,
                    booking.returnTime,
                  ),
                ),
              ],
            ),
          ),

          Divider(height: 1, color: Colors.grey.shade200),

          // =========================
          // PRICE + ACTION
          // =========================
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Total Price',
                      style: GoogleFonts.poppins(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '₱${booking.totalPrice}',
                      style: GoogleFonts.poppins(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.black,
                      ),
                    ),
                  ],
                ),
                _buildActionButton(context),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // =========================
  // STATUS BADGE
  // =========================
  Widget _statusBadge() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: status == 'active' ? Colors.black : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(8),
        border: status != 'active'
            ? Border.all(color: Colors.grey.shade300)
            : null,
      ),
      child: Text(
        _getStatusText(),
        style: GoogleFonts.poppins(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color:
              status == 'active' ? Colors.white : Colors.grey.shade700,
        ),
      ),
    );
  }

  // =========================
  // ACTION BUTTON
  // =========================
  Widget _buildActionButton(BuildContext context) {
    switch (status) {
      case 'active':
      case 'pending':
      case 'upcoming':
        return ElevatedButton(
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => BookingDetailScreen(
                  booking: booking,
                  status: status,
                ),
              ),
            );
          },
          style: ElevatedButton.styleFrom(
            backgroundColor:
                status == 'upcoming' ? Colors.white : Colors.black,
            foregroundColor:
                status == 'upcoming' ? Colors.black : Colors.white,
            side: status == 'upcoming'
                ? const BorderSide(color: Colors.black)
                : null,
            padding:
                const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
            elevation: 0,
          ),
          child: Text(
            status == 'pending' ? 'Pay Now' : 'View Details',
            style: GoogleFonts.poppins(
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
        );

      case 'past':
        return ElevatedButton(
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => SubmitReviewScreen(
                  bookingId: booking.bookingId.toString(),   // ✅

                  carId: booking.carId.toString(),           // ✅ FIX
                  carName: booking.carName,
                  carImage: booking.carImage,

                  ownerId: booking.ownerId.toString(),       // ✅ FIX
                  ownerName: booking.ownerName,              // ✅ FIX
                  ownerImage: booking.ownerImage,  
                ),
              ),
            ).then((result) {
              if (result == true && onReviewSubmitted != null) {
                onReviewSubmitted!();
              }
            });
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.black,
            padding:
                const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
            elevation: 0,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.star, size: 16, color: Colors.white),
              const SizedBox(width: 6),
              Text(
                'Rate & Review',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        );

      default:
        return const SizedBox.shrink();
    }
  }

  // =========================
  // HELPERS
  // =========================
  Widget _buildCarImage(String imagePath) {
    if (imagePath.startsWith('http')) {
      return Image.network(imagePath, fit: BoxFit.cover);
    }
    return Image.asset(imagePath, fit: BoxFit.cover);
  }

  Widget _buildDateInfo(String label, String date, String time) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: GoogleFonts.poppins(
                fontSize: 11,
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w500)),
        const SizedBox(height: 4),
        Text(date,
            style: GoogleFonts.poppins(
                fontSize: 13, fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Row(
          children: [
            Icon(Icons.access_time,
                size: 12, color: Colors.grey.shade500),
            const SizedBox(width: 4),
            Text(time,
                style: GoogleFonts.poppins(
                    fontSize: 11,
                    color: Colors.grey.shade600)),
          ],
        ),
      ],
    );
  }
}
