import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_application_1/USERS-UI/Renter/models/booking.dart';
import 'package:flutter_application_1/USERS-UI/services/booking_service.dart';

class BookingDetailScreen extends StatefulWidget {
  final Booking booking;
  final String status; // mapped UI status

  const BookingDetailScreen({
    super.key,
    required this.booking,
    required this.status,
  });

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  String? userId;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUserId();
  }

  // 🆕 LOAD USER ID
  Future<void> _loadUserId() async {
    final prefs = await SharedPreferences.getInstance();
    final loadedUserId = prefs.getString('user_id');
    
    if (mounted) {
      setState(() {
        userId = loadedUserId;
        _isLoading = false;
      });
    }
  }

  // =========================
  // STATUS HELPERS
  // =========================
  String _getStatusText() {
    switch (widget.status) {
      case 'active':
        return 'Active';
      case 'pending':
        return 'Pending Payment';
      case 'upcoming':
        return 'Confirmed';
      case 'past':
        return 'Completed';
      default:
        return widget.status;
    }
  }

  Color _getStatusColor() {
    switch (widget.status) {
      case 'active':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'upcoming':
        return Colors.blue;
      case 'past':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  // =========================
  // UI
  // =========================
  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: Colors.white,
        body: Center(child: CircularProgressIndicator(color: Colors.black)),
      );
    }

    return Scaffold(
      backgroundColor: Colors.white,
      body: CustomScrollView(
        slivers: [
          _buildAppBar(context),
          SliverToBoxAdapter(child: _buildContent(context)),
        ],
      ),
      bottomNavigationBar: _buildBottomBar(context),
    );
  }

  // =========================
  // APP BAR
  // =========================
  SliverAppBar _buildAppBar(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 300,
      pinned: true,
      backgroundColor: Colors.white,
      leading: IconButton(
        icon: _circleIcon(Icons.arrow_back),
        onPressed: () => Navigator.pop(context),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            Image.network(
              widget.booking.carImage,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                color: Colors.grey.shade200,
                child: const Icon(Icons.directions_car, size: 100),
              ),
            ),
            _imageGradient(),
            _statusBadge(),
          ],
        ),
      ),
    );
  }

  // =========================
  // MAIN CONTENT
  // =========================
  Widget _buildContent(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _carInfo(),
        _rentalPeriod(),
        const SizedBox(height: 20),
        _locationCard(),
        const SizedBox(height: 20),
        _paymentDetails(),
        const SizedBox(height: 20),
        _helpSection(),
        const SizedBox(height: 120),
      ],
    );
  }

  // =========================
  // SECTIONS
  // =========================
  Widget _carInfo() {
    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(
          widget.booking.carName,
          style: GoogleFonts.poppins(fontSize: 24, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        Row(children: [
          Icon(Icons.receipt_long, size: 16, color: Colors.grey.shade600),
          const SizedBox(width: 6),
          Text(
            'Booking ID: ${widget.booking.bookingId}',
            style: GoogleFonts.poppins(fontSize: 13, color: Colors.grey.shade600),
          ),
        ]),
      ]),
    );
  }

  Widget _rentalPeriod() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.blue.shade50,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.blue.shade100),
        ),
        child: Column(children: [
          Row(children: [
            Icon(Icons.calendar_today, color: Colors.blue.shade700),
            const SizedBox(width: 8),
            Text(
              'Rental Period',
              style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
            ),
          ]),
          const SizedBox(height: 20),
          Row(children: [
            Expanded(
              child: _dateCard(
                'Pick Up',
                widget.booking.pickupDate,
                widget.booking.pickupTime,
                Icons.arrow_circle_up,
                Colors.green,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: _dateCard(
                'Return',
                widget.booking.returnDate,
                widget.booking.returnTime,
                Icons.arrow_circle_down,
                Colors.orange,
              ),
            ),
          ]),
        ]),
      ),
    );
  }

  Widget _locationCard() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Row(children: [
          _iconBox(Icons.location_on, Colors.red),
          const SizedBox(width: 16),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Location',
                  style: GoogleFonts.poppins(fontSize: 12, color: Colors.grey)),
              const SizedBox(height: 4),
              Text(
                widget.booking.location,
                style: GoogleFonts.poppins(fontWeight: FontWeight.w600),
              ),
            ]),
          ),
          Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey.shade400),
        ]),
      ),
    );
  }

  Widget _paymentDetails() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(
          'Payment Details',
          style: GoogleFonts.poppins(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Column(children: [
            _paymentRow('Rental Fee', '₱${widget.booking.totalPrice}'),
            const Divider(height: 24),
            _paymentRow('Service Fee', '₱250', isSubtotal: true),
            const Divider(height: 24),
            _paymentRow(
              'Total Amount',
              '₱${_calculateTotal(widget.booking.totalPrice)}',
              isTotal: true,
            ),
          ]),
        ),
      ]),
    );
  }

  Widget _helpSection() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(
          'Need Help?',
          style: GoogleFonts.poppins(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 16),
        Row(children: [
          Expanded(
            child: _contactButton(
              'Message Owner',
              Icons.chat_bubble_outline,
              Colors.blue,
              () {},
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _contactButton(
              'Call Support',
              Icons.phone,
              Colors.green,
              () {},
            ),
          ),
        ]),
      ]),
    );
  }

  // =========================
  // BOTTOM BAR
  // =========================
  Widget _buildBottomBar(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha((0.08 * 255).round()),
            blurRadius: 12,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(top: false, child: _buildBottomButton(context)),
    );
  }

  // =========================
  // BUTTON LOGIC
  // =========================
  Widget _buildBottomButton(BuildContext context) {
    switch (widget.status) {
      case 'active':
        return _twoButtons(
          context,
          'Cancel Booking',
          Colors.red,
          _showCancelDialog,
          'View Trip',
          Colors.black,
        );
      case 'pending':
        return _singleButton(
          'Complete Payment',
          Colors.green.shade600,
          () => _showPaymentOptions(context),
        );
      case 'upcoming':
        return _twoButtons(
          context,
          'Modify Booking',
          Colors.grey,
          _showCancelDialog,
          'Get Directions',
          Colors.black,
        );
      case 'past':
        return _singleButton(
          'Book This Car Again',
          Colors.black,
          () {},
        );
      default:
        return const SizedBox.shrink();
    }
  }

  // =========================
  // UTILITIES
  // =========================
  String _calculateTotal(String fee) {
    final value = int.tryParse(fee.replaceAll(',', '')) ?? 0;
    return (value + 250).toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
  }

  // =========================
  // DIALOGS
  // =========================
  void _showCancelDialog(BuildContext context) {
    if (userId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('User session expired. Please login again.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Cancel Booking?'),
        content: const Text(
          'Are you sure you want to cancel this booking? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('No'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx); // close dialog

              // Show loading
              showDialog(
                context: context,
                barrierDismissible: false,
                builder: (_) => Center(
                  child: Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const CircularProgressIndicator(color: Colors.black),
                        const SizedBox(height: 16),
                        Text(
                          'Cancelling booking...',
                          style: GoogleFonts.poppins(fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                ),
              );

              final success = await BookingService.cancelBooking(
                bookingId: widget.booking.bookingId,
                 userId: int.parse(userId!),
              );

              Navigator.pop(context); // close loading

              if (success) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Booking cancelled successfully'),
                    backgroundColor: Colors.green,
                  ),
                );

                Navigator.pop(context, true); // go back & refresh
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Failed to cancel booking'),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Cancel Booking'),
          ),
        ],
      ),
    );
  }

  void _showPaymentOptions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (_) => const SizedBox(height: 200),
    );
  }

  // =========================
  // SMALL HELPERS
  // =========================
  Widget _circleIcon(IconData icon) => Container(
        padding: const EdgeInsets.all(8),
        decoration:
            const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
        child: Icon(icon),
      );

  Widget _imageGradient() => Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Colors.transparent,
              Colors.black.withAlpha((0.3 * 255).round())
            ],
          ),
        ),
      );

  Widget _statusBadge() => Positioned(
        top: 60,
        right: 16,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: _getStatusColor(),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            _getStatusText(),
            style: GoogleFonts.poppins(color: Colors.white, fontSize: 12),
          ),
        ),
      );

  Widget _iconBox(IconData icon, Color color) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withAlpha((0.1 * 255).round()),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Icon(icon, color: color),
      );

  Widget _dateCard(
    String label,
    String date,
    String time,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 6),
          Text(label, style: GoogleFonts.poppins(fontSize: 11)),
        ]),
        const SizedBox(height: 8),
        Text(date, style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
        const SizedBox(height: 4),
        Text(time, style: GoogleFonts.poppins(fontSize: 11)),
      ]),
    );
  }

  Widget _paymentRow(
    String label,
    String value, {
    bool isSubtotal = false,
    bool isTotal = false,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.w500,
          ),
        ),
        Text(
          value,
          style: GoogleFonts.poppins(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _contactButton(
    String label,
    IconData icon,
    Color color,
    VoidCallback onTap,
  ) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: color.withAlpha((0.1 * 255).round()),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(icon, color: color),
          const SizedBox(width: 8),
          Text(label, style: GoogleFonts.poppins(color: color)),
        ]),
      ),
    );
  }

  Widget _singleButton(String label, Color color, VoidCallback onTap) {
    return ElevatedButton(
      onPressed: onTap,
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      child: Text(
        label,
        style: GoogleFonts.poppins(
          color: Colors.white,
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _twoButtons(
    BuildContext context,
    String leftLabel,
    Color leftColor,
    Function(BuildContext) leftAction,
    String rightLabel,
    Color rightColor,
  ) {
    return Row(children: [
      Expanded(
        child: OutlinedButton(
          onPressed: () => leftAction(context),
          style: OutlinedButton.styleFrom(
            side: BorderSide(color: leftColor),
            padding: const EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: Text(
            leftLabel,
            style: GoogleFonts.poppins(
              color: leftColor,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: ElevatedButton(
          onPressed: () {},
          style: ElevatedButton.styleFrom(
            backgroundColor: rightColor,
            padding: const EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: Text(
            rightLabel,
            style: GoogleFonts.poppins(
              color: Colors.white,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    ]);
  }
}