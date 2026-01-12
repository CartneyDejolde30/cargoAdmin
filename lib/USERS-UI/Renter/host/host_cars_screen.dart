import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:animate_do/animate_do.dart';
import '../car_detail_screen.dart';

class HostCarsScreen extends StatefulWidget {
  final String ownerId;
  final String ownerName;

  const HostCarsScreen({
    super.key,
    required this.ownerId,
    required this.ownerName,
  });

  @override
  State<HostCarsScreen> createState() => _HostCarsScreenState();
}

class _HostCarsScreenState extends State<HostCarsScreen> {
  bool loading = true;
  List<Map<String, dynamic>> cars = [];

  final String baseUrl = "http://10.139.150.2/carGOAdmin/";

  @override
  void initState() {
    super.initState();
    fetchOwnerCars();
  }

  String formatImage(String path) {
    if (path.isEmpty) return "https://via.placeholder.com/300";
    if (path.startsWith("http")) return path;
    
    // ✅ Better image cleaning
    String cleanPath = path.replaceAll(RegExp(r'uploads/+'), '');
    return "${baseUrl}uploads/$cleanPath";
  }

  Future<void> fetchOwnerCars() async {
  setState(() => loading = true);

  final url = Uri.parse("${baseUrl}api/get_owner_cars.php?owner_id=${widget.ownerId}");

  print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
  print("🔍 FETCHING OWNER CARS");
  print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
  print("Owner ID: ${widget.ownerId}");
  print("URL: $url");
  
  try {
    final response = await http.get(url);

    print("📡 Response Status: ${response.statusCode}");
    print("📦 Response Body: ${response.body}");
    print("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    if (response.statusCode == 200) {
      final result = jsonDecode(response.body);

      if (result["status"] == "success") {
        setState(() {
          cars = List<Map<String, dynamic>>.from(result["cars"]);
          print("✅ Loaded ${cars.length} cars for owner ${widget.ownerId}");
        });
      } else {
        print("❌ API Error: ${result["message"]}");
      }
    } else {
      print("❌ HTTP Error: ${response.statusCode}");
    }
  } catch (e) {
    print("❌ ERROR: $e");
  }

  setState(() => loading = false);
}

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              "${widget.ownerName}'s Cars",
              style: GoogleFonts.poppins(
                color: Colors.black,
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
            if (!loading)
              Text(
                "${cars.length} vehicle${cars.length != 1 ? 's' : ''} available",
                style: GoogleFonts.poppins(
                  color: Colors.grey,
                  fontSize: 12,
                ),
              ),
          ],
        ),
      ),

      body: loading
          ? const Center(child: CircularProgressIndicator(color: Colors.black))
          : cars.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: fetchOwnerCars,
                  child: GridView.builder(
                    padding: const EdgeInsets.all(20),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      crossAxisSpacing: 16,
                      mainAxisSpacing: 16,
                      childAspectRatio: 0.68,
                    ),
                    itemCount: cars.length,
                    itemBuilder: (context, index) {
                      final car = cars[index];
                      return FadeInUp(
                        duration: Duration(milliseconds: 300 + (index * 100)),
                        child: _buildCarCard(car, index),
                      );
                    },
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.directions_car_outlined,
            size: 100,
            color: Colors.grey.shade400,
          ),
          const SizedBox(height: 16),
          Text(
            "No cars available",
            style: GoogleFonts.poppins(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: Colors.grey.shade600,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            "This owner hasn't listed any cars yet",
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.grey.shade500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCarCard(Map<String, dynamic> car, int index) {
    final imageUrl = formatImage(car['image'] ?? "");
    final carName = "${car['brand'] ?? 'Unknown'} ${car['model'] ?? 'Car'}";
    final year = car['car_year'] ?? "";
    final rating = double.tryParse(car['rating']?.toString() ?? "5.0") ?? 5.0;
    
    // ✅ Better location handling
    final location = (car['location']?.toString().isNotEmpty ?? false) 
        ? car['location'] 
        : "Agusan del Sur";
    
    // ✅ Better price handling
    final price = car['price']?.toString() ?? 
                  car['price_per_day']?.toString() ?? 
                  "0";
    
    final seats = int.tryParse(
      car['seat']?.toString() ?? 
      car['seats']?.toString() ?? 
      "4"
    ) ?? 4;
    
    final hasUnlimitedMileage = (car['has_unlimited_mileage']?.toString() == "1");
    final carId = int.tryParse(car['id']?.toString() ?? "0") ?? 0;

    return GestureDetector(
      onTap: () {
        // ✅ Add validation
        if (carId <= 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Invalid car data")),
          );
          return;
        }

        print("🚗 Navigating to car: $carName (ID: $carId)");

        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => CarDetailScreen(
              carId: carId,
              carName: carName,
              carImage: imageUrl,
              price: price,
              rating: rating,
              location: location,
            ),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Car Image
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                  child: Image.network(
                    imageUrl,
                    height: 140,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    loadingBuilder: (context, child, loadingProgress) {
                      if (loadingProgress == null) return child;
                      return Container(
                        height: 140,
                        color: Colors.grey.shade200,
                        child: Center(
                          child: CircularProgressIndicator(
                            color: Colors.black,
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded /
                                    loadingProgress.expectedTotalBytes!
                                : null,
                          ),
                        ),
                      );
                    },
                    errorBuilder: (_, __, ___) => Container(
                      height: 140,
                      color: Colors.grey.shade200,
                      child: Icon(
                        Icons.broken_image,
                        size: 60,
                        color: Colors.grey.shade400,
                      ),
                    ),
                  ),
                ),
                // Rating badge
                Positioned(
                  top: 12,
                  right: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 4,
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.star, color: Colors.orange, size: 14),
                        const SizedBox(width: 4),
                        Text(
                          rating.toStringAsFixed(1),
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                // Unlimited mileage badge
                if (hasUnlimitedMileage)
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        "Unlimited",
                        style: GoogleFonts.poppins(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
              ],
            ),

            // Car Details
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Price
                    Text(
                      "₱$price/day",
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.green.shade700,
                      ),
                    ),
                    const SizedBox(height: 4),

                    // Car name and year
                    Text(
                      year.isNotEmpty ? "$carName $year" : carName,
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.black87,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),

                    // Location
                    Row(
                      children: [
                        Icon(Icons.location_on, size: 12, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            location,
                            style: GoogleFonts.poppins(
                              fontSize: 11,
                              color: Colors.grey.shade600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),

                    const Spacer(),

                    // Seats info
                    Row(
                      children: [
                        Icon(Icons.event_seat, size: 12, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          "$seats-seater",
                          style: GoogleFonts.poppins(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}