import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:google_fonts/google_fonts.dart';
import '../Renter/search_filter_screen.dart';
import '../Renter/widgets/bottom_nav_bar.dart';
import 'car_detail_screen.dart';
import '../Renter/chats/chat_list_screen.dart';

class CarListScreen extends StatefulWidget {
  final String title;

  const CarListScreen({
    super.key,
    this.title = 'Search',
  });

  @override
  State<CarListScreen> createState() => _CarListScreenState();
}

class _CarListScreenState extends State<CarListScreen> {
  String _selectedCategory = 'All';
  int _selectedNavIndex = 0;

  List<Map<String, dynamic>> _allCars = [];
  List<Map<String, dynamic>> _filteredCars = [];
  bool _loading = true;

  // Active filters
  Map<String, dynamic>? _activeFilters;
  int _activeFilterCount = 0;

  final List<String> _categories = ['All', 'SUV', 'Sedan', 'Sport', 'Coupe', 'Luxury'];

  @override
  void initState() {
    super.initState();
    fetchCars();
  }

  String getImageUrl(String path) {
    if (path.isEmpty) {
      return "https://via.placeholder.com/300";
    }
    return "http://10.139.150.2/carGOAdmin/uploads/${path.replaceFirst("uploads/", "")}";
  }

  Future<void> fetchCars() async {
    const url = "http://10.139.150.2/carGOAdmin/api/get_cars.php";

    try {
      final res = await http.get(Uri.parse(url));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'success') {
          setState(() {
            _allCars = List<Map<String, dynamic>>.from(data['cars']);
            _applyFilters();
          });
        }
      }
    } catch (e) {
      print("❌ ERROR LOADING CARS: $e");
    }

    setState(() => _loading = false);
  }

  void _applyFilters() {
    List<Map<String, dynamic>> filtered = List.from(_allCars);

    // Apply category filter
    if (_selectedCategory != 'All') {
      filtered = filtered.where((car) {
        String carCategory = (car['category'] ?? '').toString().toLowerCase();
        return carCategory == _selectedCategory.toLowerCase();
      }).toList();
    }

    // Apply search filters if active
    if (_activeFilters != null) {
      // Location filter
      if (_activeFilters!['location'] != null && 
          _activeFilters!['location'].toString().isNotEmpty) {
        String searchLocation = _activeFilters!['location'].toString().toLowerCase();
        filtered = filtered.where((car) {
          String carLocation = (car['location'] ?? '').toString().toLowerCase();
          return carLocation.contains(searchLocation) || 
                 searchLocation.contains(carLocation);
        }).toList();
      }

      // Vehicle Type filter (map to category)
      if (_activeFilters!['vehicleType'] != null && 
          _activeFilters!['vehicleType'].toString().isNotEmpty) {
        String vehicleType = _activeFilters!['vehicleType'].toString().toLowerCase();
        filtered = filtered.where((car) {
          // Map vehicle types: Car -> All categories, Motorcycle -> Sport
          if (vehicleType == 'motorcycle') {
            return (car['category'] ?? '').toString().toLowerCase() == 'sport';
          }
          return true; // Car includes all types
        }).toList();
      }

      // Price Range filter
      double minPrice = (_activeFilters!['minPrice'] ?? 0).toDouble();
      double maxPrice = (_activeFilters!['maxPrice'] ?? 2000).toDouble();
      
      filtered = filtered.where((car) {
        double carPrice = double.tryParse(car['price'].toString()) ?? 0;
        return carPrice >= minPrice && carPrice <= maxPrice;
      }).toList();
    }

    setState(() {
      _filteredCars = filtered;
      _calculateActiveFilters();
    });
  }

  void _calculateActiveFilters() {
    int count = 0;
    if (_activeFilters != null) {
      if (_activeFilters!['location'] != null && 
          _activeFilters!['location'].toString().isNotEmpty) count++;
      if (_activeFilters!['vehicleType'] != null && 
          _activeFilters!['vehicleType'].toString().isNotEmpty) count++;
      if (_activeFilters!['deliveryMethod'] != null && 
          _activeFilters!['deliveryMethod'].toString().isNotEmpty) count++;
      
      double minPrice = (_activeFilters!['minPrice'] ?? 0).toDouble();
      double maxPrice = (_activeFilters!['maxPrice'] ?? 2000).toDouble();
      if (minPrice > 0 || maxPrice < 2000) count++;
    }
    _activeFilterCount = count;
  }

  void _clearAllFilters() {
    setState(() {
      _activeFilters = null;
      _activeFilterCount = 0;
      _selectedCategory = 'All';
      _applyFilters();
    });
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('All filters cleared'),
        backgroundColor: Colors.black,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _handleNavigation(int index) {
    setState(() => _selectedNavIndex = index);

    if (index == 0) Navigator.pop(context);
    if (index == 3) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => const ChatListScreen()));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      resizeToAvoidBottomInset: false,
      body: SafeArea(
        child: _loading
            ? _buildLoading()
            : CustomScrollView(
                slivers: [
                  _buildSliverAppBar(),
                  SliverPadding(
                    padding: const EdgeInsets.all(20),
                    sliver: SliverList(
                      delegate: SliverChildListDelegate([
                        _buildSearchBar(),
                        const SizedBox(height: 20),
                        _buildCategoryFilter(),
                        if (_activeFilterCount > 0) ...[
                          const SizedBox(height: 16),
                          _buildActiveFiltersChip(),
                        ],
                        const SizedBox(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              _filteredCars.isEmpty ? "No cars found" : "Available Cars (${_filteredCars.length})",
                              style: GoogleFonts.poppins(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                      ]),
                    ),
                  ),
                  _buildCarGrid(),
                ],
              ),
      ),
      bottomNavigationBar: BottomNavBar(
        currentIndex: _selectedNavIndex,
        onTap: _handleNavigation,
      ),
    );
  }

  Widget _buildActiveFiltersChip() {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.black,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.filter_list, color: Colors.white, size: 16),
              const SizedBox(width: 6),
              Text(
                '$_activeFilterCount ${_activeFilterCount == 1 ? 'Filter' : 'Filters'} Active',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),
        GestureDetector(
          onTap: _clearAllFilters,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: Colors.grey.shade300),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.clear, color: Colors.black, size: 16),
                const SizedBox(width: 6),
                Text(
                  'Clear All',
                  style: GoogleFonts.poppins(
                    color: Colors.black,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSliverAppBar() {
    return SliverAppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      pinned: false,
      floating: true,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back, color: Colors.black),
        onPressed: () => Navigator.pop(context),
      ),
      title: Text(
        widget.title,
        style: GoogleFonts.poppins(
          color: Colors.black,
          fontSize: 18,
          fontWeight: FontWeight.w600,
        ),
      ),
      centerTitle: true,
      actions: [
        IconButton(
          icon: const Icon(Icons.more_vert, color: Colors.black),
          onPressed: () {},
        ),
      ],
    );
  }

  Widget _buildLoading() => const Center(child: CircularProgressIndicator());

  Widget _buildSearchBar() {
    return GestureDetector(
      onTap: () async {
        final result = await Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => SearchFilterScreen(
              currentFilters: _activeFilters,
            ),
          ),
        );

        if (result != null && result is Map<String, dynamic>) {
          setState(() {
            _activeFilters = result;
            _applyFilters();
          });
          
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Filters applied - ${_filteredCars.length} cars found'),
              backgroundColor: Colors.black,
              duration: const Duration(seconds: 2),
            ),
          );
        }
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Icon(
              Icons.filter_list, 
              color: _activeFilterCount > 0 ? Colors.black : Colors.grey, 
              size: 22
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                _activeFilterCount > 0 
                    ? "$_activeFilterCount filter${_activeFilterCount > 1 ? 's' : ''} applied"
                    : "Filter & search cars...",
                style: GoogleFonts.poppins(
                  color: _activeFilterCount > 0 ? Colors.black : Colors.grey, 
                  fontSize: 14,
                  fontWeight: _activeFilterCount > 0 ? FontWeight.w600 : FontWeight.normal,
                ),
              ),
            ),
            if (_activeFilterCount > 0)
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: Colors.black,
                  shape: BoxShape.circle,
                ),
                child: Text(
                  '$_activeFilterCount',
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryFilter() {
    return SizedBox(
      height: 40,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _categories.length,
        itemBuilder: (context, index) {
          final category = _categories[index];
          final isSelected = _selectedCategory == category;

          return GestureDetector(
            onTap: () {
              setState(() {
                _selectedCategory = category;
                _applyFilters();
              });
            },
            child: Container(
              margin: const EdgeInsets.only(right: 12),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: isSelected ? Colors.black : Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: isSelected
                    ? [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ]
                    : [],
              ),
              child: Text(
                category,
                style: GoogleFonts.poppins(
                  color: isSelected ? Colors.white : Colors.black,
                  fontSize: 13,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildCarGrid() {
    if (_filteredCars.isEmpty) {
      return SliverToBoxAdapter(
        child: Container(
          padding: const EdgeInsets.all(40),
          child: Column(
            children: [
              Icon(
                Icons.search_off,
                size: 80,
                color: Colors.grey.shade400,
              ),
              const SizedBox(height: 16),
              Text(
                'No cars found',
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Try adjusting your filters',
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                ),
              ),
              if (_activeFilterCount > 0) ...[
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: _clearAllFilters,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(
                    'Clear Filters',
                    style: GoogleFonts.poppins(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      );
    }

    return SliverToBoxAdapter(
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 20),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          crossAxisSpacing: 16,
          mainAxisSpacing: 16,
          childAspectRatio: 0.60,
        ),
        itemCount: _filteredCars.length,
        itemBuilder: (context, index) {
          final car = _filteredCars[index];
          return _buildCarCard(
            carId: int.tryParse(car['id'].toString()) ?? 0,
            name: "${car['brand']} ${car['model']}",
            year: car['car_year'] ?? "",
            rating: double.tryParse(car['rating'].toString()) ?? 5.0,
            location: (car['location'] ?? '').isEmpty ? "Unknown" : car['location'],
            price: car['price'].toString(),
            seats: int.tryParse(car['seat'].toString()) ?? 4,
            transmission: car['transmission'] ?? "Automatic",
            image: getImageUrl(car['image']),
            hasUnlimitedMileage: car['has_unlimited_mileage'] == 1,
          );
        },
      ),
    );
  }

  Widget _buildCarCard({
    required int carId,
    required String name,
    required String year,
    required double rating,
    required String location,
    required String price,
    required int seats,
    required String transmission,
    required String image,
    bool hasUnlimitedMileage = false,
  }) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => CarDetailScreen(
              carId: carId,
              carName: name,
              carImage: image,
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
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                  child: Image.network(
                    image,
                    height: 140,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      height: 140,
                      color: Colors.grey.shade200,
                      child: const Icon(Icons.broken_image, size: 60, color: Colors.grey),
                    ),
                  ),
                ),
                if (hasUnlimitedMileage)
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        "Unlimited Mileage",
                        style: GoogleFonts.poppins(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: Colors.black,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "₱${price}",
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      "$name $year",
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.black87,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
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
                        const SizedBox(width: 8),
                        Icon(Icons.speed, size: 12, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            transmission,
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