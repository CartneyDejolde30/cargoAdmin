import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'host_cars_screen.dart';
import 'host_reviews_screen.dart';
import 'package:flutter_application_1/USERS-UI/Reporting/report_screen.dart';
import 'package:flutter_application_1/USERS-UI/Reporting/submit_review_screen.dart';  
import 'package:shared_preferences/shared_preferences.dart';  

class HostProfileScreen extends StatefulWidget {
  final String ownerId;
  final String ownerName;
  final String ownerImage;

  const HostProfileScreen({
    super.key,
    required this.ownerId,
    required this.ownerName,
    required this.ownerImage,
  });

  @override
  State<HostProfileScreen> createState() => _HostProfileScreenState();
}

class _HostProfileScreenState extends State<HostProfileScreen> {
  bool loading = true;
  Map<String, dynamic>? ownerData;
  int totalCars = 0;
  int totalReviews = 0;
  double averageRating = 0.0;

  final String baseUrl = "http://10.139.150.2/carGOAdmin/";

  @override
  void initState() {
    super.initState();
    fetchOwnerProfile();
  }

  String formatImage(String path) {
    if (path.isEmpty) return "https://via.placeholder.com/150";
    if (path.startsWith("http")) return path;
    return "$baseUrl$path";
  }

  Future<void> fetchOwnerProfile() async {
    final url = Uri.parse("${baseUrl}api/get_owner_profile.php?owner_id=${widget.ownerId}");

    print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    print("🔍 FETCHING OWNER PROFILE");
    print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    print("Owner ID: ${widget.ownerId}");
    print("Owner ID Type: ${widget.ownerId.runtimeType}");
    print("Owner Name: ${widget.ownerName}");
    print("URL: $url");
    print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    try {
      final response = await http.get(url);
      
      print("📡 Response Status: ${response.statusCode}");
      print("📦 Response Body: ${response.body}");

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);

        print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        print("📊 API RESPONSE:");
        print("Status: ${result["status"]}");
        
        if (result["status"] == "success") {
          print("✅ SUCCESS - Owner Data:");
          print("  - Owner Name: ${result["owner"]?["fullname"]}");
          print("  - Total Cars: ${result["total_cars"]}");
          print("  - Total Reviews: ${result["total_reviews"]}");
          print("  - Average Rating: ${result["average_rating"]}");
          print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
          
          setState(() {
            ownerData = result["owner"];
            totalCars = result["total_cars"] ?? 0;
            totalReviews = result["total_reviews"] ?? 0;
            averageRating = double.tryParse(result["average_rating"].toString()) ?? 0.0;
            loading = false;
          });
          
          print("✅ State Updated:");
          print("  - totalCars in state: $totalCars");
          print("  - totalReviews in state: $totalReviews");
        } else {
          print("❌ API Error: ${result["message"]}");
          print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
          setState(() => loading = false);
        }
      } else {
        print("❌ HTTP Error: ${response.statusCode}");
        setState(() => loading = false);
      }
    } catch (e) {
      print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      print("❌ EXCEPTION CAUGHT:");
      print("Error: $e");
      print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      setState(() => loading = false);
    }
  }

  // ⭐ NEW METHOD ADDED
  Future<bool> _checkIfUserRentedFromHost(String userId) async {
    try {
      final response = await http.get(
        Uri.parse("${baseUrl}api/check_user_host_rental.php?user_id=$userId&owner_id=${widget.ownerId}"),
      );
      
      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        return result['has_rented'] == true;
      }
      return false;
    } catch (e) {
      print("❌ Error checking rental: $e");
      return false;
    }
  }

  void _showReportBottomSheet(BuildContext context, String ownerName) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.flag, color: Colors.red, size: 24),
              ),
              title: Text(
                'Report User',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              subtitle: Text(
                'Report inappropriate behavior',
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
              onTap: () {
                Navigator.pop(ctx);
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => ReportScreen(
                      reportType: 'user',
                      reportedId: widget.ownerId,
                      reportedName: ownerName,
                    ),
                  ),
                );
              },
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return Scaffold(
        backgroundColor: Colors.grey.shade50,
        body: const Center(
          child: CircularProgressIndicator(color: Colors.black),
        ),
      );
    }

    final profileImage = formatImage(ownerData?["profile_image"] ?? widget.ownerImage);
    final fullName = ownerData?["fullname"] ?? widget.ownerName;
    final occupation = ownerData?["occupation"] ?? "";
    final joinedYear = ownerData?["created_at"] != null
        ? DateTime.parse(ownerData!["created_at"]).year.toString()
        : "2025";

    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              const SizedBox(height: 20),

              // Header Row with Back and Report buttons
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Back Button
                    GestureDetector(
                      onTap: () => Navigator.pop(context),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: const BoxDecoration(
                          color: Colors.black,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.arrow_back,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                    ),
                    
                    // Report Button
                    GestureDetector(
                      onTap: () {
                        _showReportBottomSheet(context, fullName);
                      },
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade200,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.more_horiz,
                          color: Colors.black,
                          size: 20,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 30),

              // Title
              Text(
                "Host Profile",
                style: GoogleFonts.poppins(
                  fontSize: 24,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade600,
                ),
              ),

              const SizedBox(height: 40),

              // Profile Picture with Badge
              Stack(
                children: [
                  Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.grey.shade300, width: 2),
                    ),
                    child: CircleAvatar(
                      radius: 65,
                      backgroundColor: Colors.grey.shade300,
                      backgroundImage: NetworkImage(profileImage),
                      onBackgroundImageError: (_, __) {},
                      child: profileImage.contains("placeholder")
                          ? const Icon(Icons.person, size: 65, color: Colors.white70)
                          : null,
                    ),
                  ),
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.yellow.shade600,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.person,
                        size: 20,
                        color: Colors.black,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 20),

              // Owner Name
              Text(
                fullName,
                style: GoogleFonts.poppins(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),

              const SizedBox(height: 8),

              // Star Rating
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ...List.generate(5, (index) {
                    return Icon(
                      index < averageRating.floor()
                          ? Icons.star
                          : Icons.star_border,
                      color: index < averageRating.floor()
                          ? Colors.yellow.shade600
                          : Colors.grey.shade400,
                      size: 28,
                    );
                  }),
                  const SizedBox(width: 8),
                  Text(
                    totalReviews.toString(),
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              // Joined Date
              Text(
                "JOINED $joinedYear",
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: Colors.grey.shade500,
                  letterSpacing: 1,
                ),
              ),

              const SizedBox(height: 40),

              // Divider
              Divider(
                color: Colors.grey.shade300,
                thickness: 1,
                indent: 40,
                endIndent: 40,
              ),

              const SizedBox(height: 30),

              // Car Owner Description Section
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Car Owner Description",
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Name Field
                    Text(
                      "Name",
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      fullName,
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Occupation Field
                    Text(
                      "Occupation",
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      occupation.isEmpty ? "Not specified" : occupation,
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        color: occupation.isEmpty ? Colors.grey.shade400 : Colors.black,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 30),

              // Divider
              Divider(
                color: Colors.grey.shade300,
                thickness: 1,
                indent: 40,
                endIndent: 40,
              ),

              const SizedBox(height: 20),

              // Cars Section
              _buildMenuTile(
                title: "Cars",
                subtitle: "$totalCars vehicle${totalCars != 1 ? 's' : ''}",
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => HostCarsScreen(
                        ownerId: widget.ownerId,
                        ownerName: fullName,
                      ),
                    ),
                  );
                },
              ),

              const SizedBox(height: 16),

              // Reviews Section
              _buildMenuTile(
                title: "Reviews",
                subtitle: "$totalReviews review${totalReviews != 1 ? 's' : ''}",
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => OwnerReviewsScreen(
                        ownerId: widget.ownerId,
                        ownerName: fullName,
                        averageRating: averageRating,
                      ),
                    ),
                  );
                },
              ),

              const SizedBox(height: 16),

              // ⭐ NEW: Rate Host Button
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 40),
                child: InkWell(
                  onTap: () async {
                    // Get user ID
                    final prefs = await SharedPreferences.getInstance();
                    final userId = prefs.getString('user_id');
                    
                    if (userId == null || userId.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Please login to leave a review'),
                          backgroundColor: Colors.orange,
                        ),
                      );
                      return;
                    }
                    
                    // Check if user has rented from this host
                    final hasRented = await _checkIfUserRentedFromHost(userId);
                    
                    if (!hasRented) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('You need to complete a rental from this host first'),
                          backgroundColor: Colors.orange,
                          duration: Duration(seconds: 3),
                        ),
                      );
                      return;
                    }
                    
                    // Navigate to review screen
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => SubmitReviewScreen(
                          bookingId: '', // Not tied to specific booking
                          carId: '', // Not specific to one car
                          carName: '$fullName\'s Service',
                          carImage: widget.ownerImage,
                          ownerId: widget.ownerId,
                          ownerName: fullName,
                          ownerImage: ownerData?["profile_image"] ?? widget.ownerImage,
                        ),
                      ),
                    ).then((result) {
                      if (result == true) {
                        fetchOwnerProfile(); // Refresh profile data
                      }
                    });
                  },
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.blue.shade600, Colors.purple.shade600],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.blue.withOpacity(0.3),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.star_rate, color: Colors.white, size: 24),
                        const SizedBox(width: 10),
                        Text(
                          'Rate This Host',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMenuTile({
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40),
      child: InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 20),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: Colors.black,
                    ),
                  ),
                  if (subtitle.isNotEmpty)
                    Text(
                      subtitle,
                      style: GoogleFonts.poppins(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                ],
              ),
              Icon(
                Icons.arrow_forward_ios,
                size: 18,
                color: Colors.grey.shade700,
              ),
            ],
          ),
        ),
      ),
    );
  }
}