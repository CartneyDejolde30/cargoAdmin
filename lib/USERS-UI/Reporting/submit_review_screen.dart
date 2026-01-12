import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class SubmitReviewScreen extends StatefulWidget {
  final String bookingId;
  final String carId;
  final String carName;
  final String carImage;
  final String ownerId;
  final String ownerName;
  final String ownerImage;

  const SubmitReviewScreen({
    super.key,
    required this.bookingId,
    required this.carId,
    required this.carName,
    required this.carImage,
    required this.ownerId,
    required this.ownerName,
    this.ownerImage = '',
  });

  @override
  State<SubmitReviewScreen> createState() => _SubmitReviewScreenState();
}

class _SubmitReviewScreenState extends State<SubmitReviewScreen> {
  final TextEditingController _carReviewController = TextEditingController();
  final TextEditingController _ownerReviewController = TextEditingController();
  bool isSubmitting = false;

  final String baseUrl = "http://10.139.150.2/carGOAdmin/";

  // Review categories for cars
  Map<String, double> carCategories = {
    'Cleanliness': 0,
    'Condition': 0,
    'Accuracy': 0,
    'Value': 0,
  };

  // Review categories for owners
  Map<String, double> ownerCategories = {
    'Communication': 0,
    'Responsiveness': 0,
    'Friendliness': 0,
  };

  // Quick comment suggestions
  final List<String> carComments = [
    'Great car and excellent condition!',
    'Very clean and well-maintained',
    'Exactly as described',
    'Good value for money',
  ];

  final List<String> ownerComments = [
    'Excellent host!',
    'Great communication',
    'Very responsive and helpful',
    'Highly recommended!',
  ];

  @override
  void dispose() {
    _carReviewController.dispose();
    _ownerReviewController.dispose();
    super.dispose();
  }

  String formatImage(String path) {
    if (path.isEmpty) return "https://via.placeholder.com/150";
    if (path.startsWith("http")) return path;
    return "$baseUrl$path";
  }

  double _calculateAverageCarRating() {
    if (carCategories.isEmpty) return 0;
    double sum = carCategories.values.reduce((a, b) => a + b);
    return sum / carCategories.length;
  }

  double _calculateAverageOwnerRating() {
    if (ownerCategories.isEmpty) return 0;
    double sum = ownerCategories.values.reduce((a, b) => a + b);
    return sum / ownerCategories.length;
  }

  Future<void> _submitReviews() async {

    final bookingId = int.tryParse(widget.bookingId) ?? 0;
  final carId = int.tryParse(widget.carId) ?? 0;
  final ownerId = int.tryParse(widget.ownerId) ?? 0;

  if (bookingId == 0 || carId == 0 || ownerId == 0) {
    _showSnackBar('Invalid booking data', Colors.red);
    return;
  }
    final avgCarRating = _calculateAverageCarRating();
    final avgOwnerRating = _calculateAverageOwnerRating();

    if (avgCarRating == 0 || avgOwnerRating == 0) {
      _showSnackBar('Please rate all categories', Colors.orange);
      return;
    }

    if (_carReviewController.text.trim().isEmpty || 
        _ownerReviewController.text.trim().isEmpty) {
      _showSnackBar('Please write your reviews', Colors.orange);
      return;
    }

    setState(() => isSubmitting = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('user_id') ?? '';

      final url = Uri.parse("${baseUrl}api/submit_review.php");
     final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'booking_id': int.tryParse(widget.bookingId) ?? 0,
        'user_id': int.tryParse(userId) ?? 0,
        'car_id': int.tryParse(widget.carId) ?? 0,
        'owner_id': int.tryParse(widget.ownerId) ?? 0,

        'car_rating': avgCarRating,
        'owner_rating': avgOwnerRating,

        'car_review': _carReviewController.text.trim(),
        'owner_review': _ownerReviewController.text.trim(),

        'car_categories': carCategories,
        'owner_categories': ownerCategories,
      }),


    );


      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        
        if (result['status'] == 'success') {
          if (mounted) {
            Navigator.pop(context, true);
            _showSnackBar('Thank you for your review!', Colors.green, icon: Icons.check_circle);
          }
        } else {
          throw Exception(result['message'] ?? 'Failed to submit review');
        }
      } else {
        throw Exception('Server error');
      }
    } catch (e) {
      if (mounted) {
        _showSnackBar('Error: $e', Colors.red);
      }
    } finally {
      if (mounted) {
        setState(() => isSubmitting = false);
      }
    }
  }

  void _showSnackBar(String message, Color color, {IconData? icon}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            if (icon != null) ...[
              Icon(icon, color: Colors.white),
              const SizedBox(width: 8),
            ],
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final avgCarRating = _calculateAverageCarRating();
    final avgOwnerRating = _calculateAverageOwnerRating();

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.black,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.arrow_back, color: Colors.white, size: 20),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Rate Your Experience',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header message
            Container(
              margin: const EdgeInsets.all(20),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.blue.shade50, Colors.purple.shade50],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.rate_review,
                      color: Colors.blue.shade700,
                      size: 28,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Your feedback matters!',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.black87,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Help others make better decisions',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.grey.shade700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Car Info Header
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 20),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      formatImage(widget.carImage),
                      width: 80,
                      height: 60,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 80,
                        height: 60,
                        color: Colors.grey.shade200,
                        child: Icon(Icons.directions_car, color: Colors.grey),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.carName,
                          style: GoogleFonts.poppins(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Owned by ${widget.ownerName}',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 32),

            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Car Review Section
                  Text(
                    'Rate the Car',
                    style: GoogleFonts.poppins(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'How was your experience with this vehicle?',
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      color: Colors.grey.shade600,
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Car Rating Categories
                  ...carCategories.keys.map((category) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: _buildRatingCategory(
                        category,
                        carCategories[category]!,
                        _getCategoryIcon(category),
                        (rating) => setState(() => carCategories[category] = rating),
                      ),
                    );
                  }).toList(),

                  const SizedBox(height: 8),

                  // Average Car Rating Display
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: _getRatingColor(avgCarRating).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: _getRatingColor(avgCarRating).withOpacity(0.3)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Overall Car Rating',
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            if (avgCarRating > 0) ...[
                              const SizedBox(height: 4),
                              Text(
                                _getRatingLabel(avgCarRating),
                                style: GoogleFonts.poppins(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ],
                        ),
                        Row(
                          children: [
                            Icon(Icons.star, color: Colors.amber.shade600, size: 24),
                            const SizedBox(width: 6),
                            Text(
                              avgCarRating.toStringAsFixed(1),
                              style: GoogleFonts.poppins(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: _getRatingColor(avgCarRating),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Car Review Text
                  Text(
                    'Write your review',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  
                  // Quick suggestions for car
                  Text(
                    'Quick suggestions:',
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: carComments.map((comment) => GestureDetector(
                      onTap: () => setState(() => _carReviewController.text = comment),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.blue.shade50,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.blue.shade200),
                        ),
                        child: Text(
                          comment,
                          style: GoogleFonts.poppins(
                            fontSize: 11,
                            color: Colors.blue.shade900,
                          ),
                        ),
                      ),
                    )).toList(),
                  ),

                  const SizedBox(height: 12),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: TextField(
                      controller: _carReviewController,
                      maxLines: 4,
                      maxLength: 300,
                      decoration: InputDecoration(
                        hintText: 'Share details about the car condition, cleanliness, etc.',
                        hintStyle: GoogleFonts.poppins(
                          color: Colors.grey.shade400,
                          fontSize: 13,
                        ),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.all(16),
                        counterStyle: GoogleFonts.poppins(fontSize: 11),
                      ),
                      style: GoogleFonts.poppins(fontSize: 14),
                    ),
                  ),

                  const SizedBox(height: 32),
                  Divider(color: Colors.grey.shade300, thickness: 1),
                  const SizedBox(height: 32),

                  // Owner Review Section
                  Text(
                    'Rate the Host',
                    style: GoogleFonts.poppins(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Owner info card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 30,
                          backgroundColor: Colors.grey.shade300,
                          backgroundImage: widget.ownerImage.isNotEmpty
                              ? NetworkImage(formatImage(widget.ownerImage))
                              : null,
                          child: widget.ownerImage.isEmpty
                              ? const Icon(Icons.person, size: 30, color: Colors.white70)
                              : null,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                widget.ownerName,
                                style: GoogleFonts.poppins(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              Text(
                                'Car Owner',
                                style: GoogleFonts.poppins(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Owner Rating Categories
                  ...ownerCategories.keys.map((category) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: _buildRatingCategory(
                        category,
                        ownerCategories[category]!,
                        _getCategoryIcon(category),
                        (rating) => setState(() => ownerCategories[category] = rating),
                      ),
                    );
                  }).toList(),

                  const SizedBox(height: 8),

                  // Average Owner Rating Display
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: _getRatingColor(avgOwnerRating).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: _getRatingColor(avgOwnerRating).withOpacity(0.3)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Overall Host Rating',
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            if (avgOwnerRating > 0) ...[
                              const SizedBox(height: 4),
                              Text(
                                _getRatingLabel(avgOwnerRating),
                                style: GoogleFonts.poppins(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ],
                        ),
                        Row(
                          children: [
                            Icon(Icons.star, color: Colors.amber.shade600, size: 24),
                            const SizedBox(width: 6),
                            Text(
                              avgOwnerRating.toStringAsFixed(1),
                              style: GoogleFonts.poppins(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: _getRatingColor(avgOwnerRating),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Owner Review Text
                  Text(
                    'Write your review',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  
                  // Quick suggestions for owner
                  Text(
                    'Quick suggestions:',
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: ownerComments.map((comment) => GestureDetector(
                      onTap: () => setState(() => _ownerReviewController.text = comment),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.purple.shade50,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.purple.shade200),
                        ),
                        child: Text(
                          comment,
                          style: GoogleFonts.poppins(
                            fontSize: 11,
                            color: Colors.purple.shade900,
                          ),
                        ),
                      ),
                    )).toList(),
                  ),

                  const SizedBox(height: 12),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: TextField(
                      controller: _ownerReviewController,
                      maxLines: 4,
                      maxLength: 300,
                      decoration: InputDecoration(
                        hintText: 'Share your experience with the owner...',
                        hintStyle: GoogleFonts.poppins(
                          color: Colors.grey.shade400,
                          fontSize: 13,
                        ),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.all(16),
                        counterStyle: GoogleFonts.poppins(fontSize: 11),
                      ),
                      style: GoogleFonts.poppins(fontSize: 14),
                    ),
                  ),

                  const SizedBox(height: 32),

                  // Submit Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isSubmitting ? null : _submitReviews,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.black,
                        padding: const EdgeInsets.symmetric(vertical: 18),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 0,
                      ),
                      child: isSubmitting
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                              ),
                            )
                          : Text(
                              'Submit Reviews',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Privacy note
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline, size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Your reviews will be visible to other users',
                            style: GoogleFonts.poppins(
                              fontSize: 11,
                              color: Colors.grey.shade700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 40),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRatingCategory(
    String label, 
    double rating, 
    IconData icon,
    Function(double) onRatingUpdate,
  ) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: rating > 0 ? Colors.amber.withOpacity(0.3) : Colors.grey.shade200,
          width: rating > 0 ? 2 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: Colors.grey.shade700),
              const SizedBox(width: 8),
              Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(5, (index) {
              return GestureDetector(
                onTap: () => onRatingUpdate((index + 1).toDouble()),
                child: Icon(
                  index < rating ? Icons.star : Icons.star_border,
                  color: index < rating ? Colors.amber.shade600 : Colors.grey.shade400,
                  size: 32,
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  IconData _getCategoryIcon(String category) {
    final icons = {
      'Cleanliness': Icons.cleaning_services,
      'Condition': Icons.verified,
      'Accuracy': Icons.check_circle,
      'Value': Icons.attach_money,
      'Communication': Icons.chat_bubble,
      'Responsiveness': Icons.flash_on,
      'Friendliness': Icons.mood,
    };
    return icons[category] ?? Icons.star;
  }

  Color _getRatingColor(double rating) {
    if (rating >= 4.5) return Colors.green.shade700;
    if (rating >= 3.5) return Colors.blue.shade700;
    if (rating >= 2.5) return Colors.orange.shade700;
    return Colors.red.shade700;
  }

  String _getRatingLabel(double rating) {
    if (rating >= 4.5) return 'Excellent! ⭐';
    if (rating >= 3.5) return 'Very Good 👍';
    if (rating >= 2.5) return 'Good 😊';
    if (rating >= 1.5) return 'Fair 😐';
    return 'Needs Improvement';
  }
}