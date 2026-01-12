import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;

class ReviewsScreen extends StatefulWidget {
  final int carId;
  final String carName;
  final int totalReviews;        
  final double averageRating;

  const ReviewsScreen({
    super.key,
    required this.carId,
    required this.carName,
    required this.totalReviews,     
    required this.averageRating,
    
  });

  @override
  State<ReviewsScreen> createState() => _ReviewsScreenState();
}

class _ReviewsScreenState extends State<ReviewsScreen> {
  final TextEditingController _searchController = TextEditingController();

  List<Map<String, dynamic>> _reviews = [];
  List<Map<String, dynamic>> _filteredReviews = [];

  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetchReviews();
    _searchController.addListener(() {
      _filterReviews(_searchController.text.trim());
    });
  }

  Future<void> _fetchReviews() async {
    final url = Uri.parse(
        "http://10.139.150.2/carGOAdmin/api/get_reviews.php?car_id=${widget.carId}");

    final response = await http.get(url);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);

      if (data["success"] == true) {
        setState(() {
          _reviews = List<Map<String, dynamic>>.from(data["reviews"]);
          _filteredReviews = List.from(_reviews);
          _loading = false;
        });
      }
    }
  }

  void _filterReviews(String query) {
    if (query.isEmpty) {
      setState(() => _filteredReviews = List.from(_reviews));
      return;
    }

    query = query.toLowerCase();

    setState(() {
      _filteredReviews = _reviews.where((review) {
        final name = review['name'].toString().toLowerCase();
        final text = review['comment'].toString().toLowerCase();
        return name.contains(query) || text.contains(query);
      }).toList();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _formatDate(String? dateString) {
  if (dateString == null || dateString.isEmpty) return "Unknown date";
  try {
    final date = DateTime.parse(dateString);
    final diff = DateTime.now().difference(date).inDays;
    if (diff == 0) return "Today";
    if (diff == 1) return "Yesterday";
    if (diff < 7) return "$diff days ago";
    return "${(diff / 7).floor()} weeks ago";
  } catch (_) {
    return "Unknown date";
  }
}

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
        title: Text(
          "Reviews",
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Stack(
              children: [
                SingleChildScrollView(
                  padding: const EdgeInsets.only(bottom: 100),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.star,
                                color: Colors.orange, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              "${widget.averageRating} ★  |  ${widget.totalReviews} Reviews",
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 20),

                        // Search Bar
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 4,
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.search,
                                  color: Colors.grey, size: 22),
                              const SizedBox(width: 12),
                              Expanded(
                                child: TextField(
                                  controller: _searchController,
                                  decoration: InputDecoration(
                                    hintText: "Find reviews",
                                    hintStyle: GoogleFonts.poppins(
                                      color: Colors.grey,
                                      fontSize: 14,
                                    ),
                                    border: InputBorder.none,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 24),

                        // Review List
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _filteredReviews.length,
                          itemBuilder: (context, index) {
                            final r = _filteredReviews[index];
                            return _buildReviewCard(
                              name: r["name"],
                              avatar: r["avatar"],
                              rating: r["rating"],
                              date: _formatDate(r["created_at"]),
                              review: r["comment"],
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  double _calculateAverageRating() {
    if (_filteredReviews.isEmpty) return 0;
    double total = 0;
    for (var r in _filteredReviews) {
      total += r["rating"];
    }
    return (total / _filteredReviews.length);
  }

  Widget _buildReviewCard({
    required String name,
    required String avatar,
    required double rating,
    required String date,
    required String review,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: Image.network(
                  avatar,
                  width: 40,
                  height: 40,
                  fit: BoxFit.cover,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  name,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Text(
                date,
                style: GoogleFonts.poppins(
                  fontSize: 11,
                  color: Colors.grey,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: List.generate(5, (index) {
              return Icon(
                index < rating ? Icons.star : Icons.star_border,
                color: Colors.orange,
                size: 16,
              );
            }),
          ),
          const SizedBox(height: 12),
          Text(
            review,
            style: GoogleFonts.poppins(
              fontSize: 12,
              color: Colors.grey.shade700,
              height: 1.6,
            ),
          ),
        ],
      ),
    );
  }
}
