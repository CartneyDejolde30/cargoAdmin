import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;

class OwnerReviewsScreen extends StatefulWidget {
  final String ownerId;
  final String ownerName;
  final double averageRating;

  const OwnerReviewsScreen({
    super.key,
    required this.ownerId,
    required this.ownerName,
    required this.averageRating,
  });

  @override
  State<OwnerReviewsScreen> createState() => _OwnerReviewsScreenState();
}

class _OwnerReviewsScreenState extends State<OwnerReviewsScreen> {
  bool loading = true;
  List<Map<String, dynamic>> reviews = [];
  List<Map<String, dynamic>> filteredReviews = [];
  int? selectedRating; // null means "All"
  
  final String baseUrl = "http://10.139.150.2/carGOAdmin/";

  @override
  void initState() {
    super.initState();
    fetchOwnerReviews();
  }

  Future<void> fetchOwnerReviews() async {
    final url = Uri.parse("${baseUrl}api/get_owner_reviews.php?owner_id=${widget.ownerId}");

    try {
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);

        if (result["status"] == "success") {
          setState(() {
            reviews = List<Map<String, dynamic>>.from(result["reviews"]);
            filteredReviews = reviews;
            loading = false;
          });
        } else {
          setState(() {
            loading = false;
          });
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(result["message"] ?? "Failed to load reviews")),
            );
          }
        }
      } else {
        setState(() => loading = false);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Failed to load reviews")),
          );
        }
      }
    } catch (e) {
      print("❌ ERROR FETCHING OWNER REVIEWS: $e");
      setState(() => loading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Error: $e")),
        );
      }
    }
  }

  void _filterByRating(int? rating) {
    setState(() {
      selectedRating = rating;
      
      if (rating == null) {
        // Show all reviews
        filteredReviews = reviews;
      } else {
        // Filter by selected rating
        filteredReviews = reviews.where((review) {
          final reviewRating = double.tryParse(review["rating"].toString()) ?? 0;
          return reviewRating.floor() == rating;
        }).toList();
      }
    });
  }

  /*int _getCountForRating(int rating) {
    return reviews.where((review) {
      final reviewRating = double.tryParse(review["rating"].toString()) ?? 0;
      return reviewRating.floor() == rating;
    }).length;
  }*/

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,
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
            child: const Icon(
              Icons.arrow_back,
              color: Colors.white,
              size: 20,
            ),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Reviews',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator(color: Colors.black))
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Filter Section
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Filter by',
                        style: GoogleFonts.poppins(
                          fontSize: 16,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey.shade700,
                        ),
                      ),
                      const SizedBox(height: 12),
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: [
                            _buildFilterChip(5),
                            const SizedBox(width: 8),
                            _buildFilterChip(4),
                            const SizedBox(width: 8),
                            _buildFilterChip(3),
                            const SizedBox(width: 8),
                            _buildFilterChip(2),
                            const SizedBox(width: 8),
                            _buildFilterChip(1),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Divider
                Divider(height: 1, color: Colors.grey.shade300),

                // Reviews List
                Expanded(
                  child: filteredReviews.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.rate_review_outlined,
                                size: 80,
                                color: Colors.grey.shade400,
                              ),
                              const SizedBox(height: 16),
                              Text(
                                selectedRating != null
                                    ? "No $selectedRating star reviews"
                                    : "No reviews yet",
                                style: GoogleFonts.poppins(
                                  fontSize: 16,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(20),
                          itemCount: filteredReviews.length,
                          itemBuilder: (context, index) {
                            final review = filteredReviews[index];
                            return _buildReviewCard(
                              name: review["fullname"] ?? "User",
                              carName: "${review["brand"]} ${review["model"]} ${review["car_year"] ?? ''}".trim(),
                              rating: double.tryParse(review["rating"].toString()) ?? 5.0,
                              date: review["created_at"] ?? "",
                              comment: review["comment"] ?? "",
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }

  Widget _buildFilterChip(int rating) {
    final isSelected = selectedRating == rating;
    //final count = _getCountForRating(rating);

    return GestureDetector(
      onTap: () => _filterByRating(isSelected ? null : rating),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? Colors.black : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? Colors.black : Colors.grey.shade300,
            width: 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.star,
              size: 16,
              color: isSelected ? Colors.yellow.shade600 : Colors.grey.shade400,
            ),
            const SizedBox(width: 4),
            Text(
              rating.toString(),
              style: GoogleFonts.poppins(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: isSelected ? Colors.white : Colors.grey.shade600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewCard({
    required String name,
    required String carName,
    required double rating,
    required String date,
    required String comment,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Avatar
              CircleAvatar(
                radius: 20,
                backgroundColor: Colors.grey.shade800,
                child: const Icon(
                  Icons.person,
                  color: Colors.white,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              // Name
              Expanded(
                child: Text(
                  name,
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),
              ),
              // Rating Stars
              Row(
                children: [
                  Icon(
                    Icons.star,
                    color: Colors.yellow.shade600,
                    size: 18,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    rating.toString(),
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Car Name
          Text(
            '($carName)',
            style: GoogleFonts.poppins(
              fontSize: 13,
              color: Colors.grey.shade700,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 8),
          // Comment
          Text(
            comment,
            style: GoogleFonts.poppins(
              fontSize: 13,
              color: Colors.grey.shade700,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 8),
          // Date
          Text(
            _formatDate(date),
            style: GoogleFonts.poppins(
              fontSize: 11,
              color: Colors.grey.shade500,
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String dateString) {
    try {
      final date = DateTime.parse(dateString);
      final months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];
      
      // Format time in 12-hour format
      int hour = date.hour;
      String period = hour >= 12 ? 'PM' : 'AM';
      if (hour > 12) hour -= 12;
      if (hour == 0) hour = 12;
      
      String time = "${hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')} $period";
      
      return "${months[date.month - 1]} ${date.day}, ${date.year} | $time";
    } catch (e) {
      return dateString;
    }
  }
}