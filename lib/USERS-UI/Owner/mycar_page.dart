import 'package:flutter/material.dart';
import 'package:animate_do/animate_do.dart';
import 'package:google_fonts/google_fonts.dart';

import 'car_listing/car_details.dart';
import 'models/car_listing.dart';
import 'car_listing/vehicle_type_selection_screen.dart';
import 'verification/personal_info_screen.dart';

// Services
import '../Owner/mycar/car_services.dart';
import '../Owner/mycar/verification_service.dart';

// Widgets
import '../Owner/mycar/car_card.dart';
import '../Owner/mycar/car_filter_chips.dart';
import '../Owner/mycar/car_stats_section.dart';
import '../Owner/mycar/empty_car_state.dart';
import '../Owner/mycar/car_shimmer.dart';
import '../Owner/mycar/car_detail_page.dart';

// Dialogs
import '../Owner/mycar/verification_dialog.dart';

class MyCarPage extends StatefulWidget {
  final int ownerId;
  const MyCarPage({super.key, required this.ownerId});

  @override
  State<MyCarPage> createState() => _MyCarPageState();
}

class _MyCarPageState extends State<MyCarPage> {
  final CarService _carService = CarService();
  final VerificationService _verificationService = VerificationService();

  List<Map<String, dynamic>> cars = [];
  List<Map<String, dynamic>> filteredCars = [];

  bool isLoading = true;
  bool canAddCar = false;
  bool isCheckingVerification = true;

  String searchQuery = "";
  String selectedFilter = "All";

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  /* ---------------- INITIALIZE ---------------- */
  Future<void> _initialize() async {
    await checkVerificationStatus();
    await fetchCars();
  }

  /* ---------------- VERIFICATION ---------------- */
  Future<void> checkVerificationStatus() async {
    setState(() => isCheckingVerification = true);

    final result = await _verificationService.checkVerification();

    if (!mounted) return;

    setState(() {
      canAddCar = result['canAddCar'] ?? false;
      isCheckingVerification = false;
    });
  }

  /* ---------------- FETCH CARS FROM DB ---------------- */
  Future<void> fetchCars() async {
    setState(() => isLoading = true);

    final fetchedCars = await _carService.fetchCars(widget.ownerId);

    if (!mounted) return;

    setState(() {
      cars = fetchedCars;
      isLoading = false;
    });

    applyFilters();
  }

  /* ---------------- FILTER LOGIC ---------------- */
  void applyFilters() {
  final query = searchQuery.toLowerCase();

  filteredCars = cars.where((vehicle) {
    final brand = vehicle['brand']?.toString().toLowerCase() ?? "";
    final model = vehicle['model']?.toString().toLowerCase() ?? "";
    final status = vehicle['status']?.toString().toLowerCase() ?? "";
    final type = vehicle['vehicle_type']?.toString().toLowerCase() ?? "";

    final matchesSearch =
        brand.contains(query) || model.contains(query);

    final matchesFilter =
        selectedFilter == "All" ||
        selectedFilter.toLowerCase() == status ||
        selectedFilter.toLowerCase() == type;

    return matchesSearch && matchesFilter;
  }).toList();

  setState(() {});
}


  /* ---------------- DELETE CAR ---------------- */
  Future<void> deleteCar(int id) async {
    final success = await _carService.deleteCar(id);

    if (!success || !mounted) return;

    setState(() {
      cars.removeWhere((car) => car["id"].toString() == id.toString());
      applyFilters();
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle_outline, color: Colors.white),
            const SizedBox(width: 12),
            Text(
              'Car deleted successfully',
              style: GoogleFonts.poppins(fontSize: 14),
            ),
          ],
        ),
        backgroundColor: Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  /* ---------------- EDIT CAR ---------------- */
  Future<void> navigateToEditScreen(Map<String, dynamic> car) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => CarDetailsScreen(
          ownerId: widget.ownerId,
          existingListing: CarListing.fromJson(car),
        ),
      ),
    );

    if (result == true) {
      fetchCars();
    }
  }

  /* ---------------- ADD CAR ---------------- */
  Future<void> handleAddCar() async {
    if (!canAddCar) {
      VerificationDialog.show(
        context,
        onVerifyPressed: () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => const PersonalInfoScreen(),
            ),
          );

          if (result == true) {
            checkVerificationStatus();
          }
        },
      );
      return;
    }

    final result = await Navigator.push(
      context,
      PageRouteBuilder(
        pageBuilder: (_, __, ___) =>
            VehicleTypeSelectionScreen(ownerId: widget.ownerId),
        transitionsBuilder: (_, animation, __, child) =>
            FadeTransition(opacity: animation, child: child),
      ),
    );

    if (result == true) {
      fetchCars();
      checkVerificationStatus();
    }
  }

  /* ---------------- MENU ACTION ---------------- */
  void handleCarMenuAction(String action, Map<String, dynamic> car) {
    if (action == "delete") {
      deleteCar(int.parse(car["id"].toString()));
    } else if (action == "edit") {
      navigateToEditScreen(car);
    }
  }

  /* ---------------- UI ---------------- */
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: _buildAppBar(),
      floatingActionButton: _buildFAB(),
      body: RefreshIndicator(
        onRefresh: _initialize,
        color: Colors.black,
        child: Column(
          children: [
            CarStatsSection(cars: cars),
            _buildSearchBar(),
            CarFilterChips(
              selectedFilter: selectedFilter,
              onFilterChanged: (filter) {
                setState(() => selectedFilter = filter);
                applyFilters();
              },
            ),
            const SizedBox(height: 8),
            Expanded(
              child: isLoading
                  ? const CarShimmerLoader()
                  : filteredCars.isEmpty
                      ? EmptyCarState(searchQuery: searchQuery)
                      : _buildCarGrid(),
            ),
          ],
        ),
      ),
    );
  }

  /* ---------------- APP BAR ---------------- */
  AppBar _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      automaticallyImplyLeading: false,
      title: Text(
        "My Cars",
        style: GoogleFonts.poppins(
          color: Colors.black,
          fontWeight: FontWeight.bold,
          fontSize: 22,
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 16),
          child: Image.asset("assets/cargo.png", width: 32),
        )
      ],
    );
  }

  /* ---------------- FAB ---------------- */
  Widget _buildFAB() {
    return FloatingActionButton.extended(
      backgroundColor: canAddCar ? Colors.black : Colors.grey.shade400,
      onPressed: isCheckingVerification ? null : handleAddCar,
      icon: Icon(
        Icons.add,
        color: canAddCar ? Colors.white : Colors.grey.shade600,
      ),
      label: Text(
        canAddCar ? "Add Car" : "Verify First",
        style: GoogleFonts.poppins(
          fontWeight: FontWeight.w600,
          color: canAddCar ? Colors.white : Colors.grey.shade600,
        ),
      ),
    );
  }

  /* ---------------- SEARCH ---------------- */
  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      child: TextField(
        onChanged: (value) {
          searchQuery = value;
          applyFilters();
        },
        decoration: InputDecoration(
          hintText: "Search car...",
          prefixIcon: const Icon(Icons.search),
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }

  /* ---------------- GRID ---------------- */
  Widget _buildCarGrid() {
    return GridView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: filteredCars.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 14,
        mainAxisSpacing: 14,
        childAspectRatio: .65,
      ),
      itemBuilder: (_, index) {
        final car = filteredCars[index];

        return FadeInUp(
          duration: Duration(milliseconds: 200 + (index * 50)),
          child: CarCard(
            car: car,
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CarDetailPage(
                    car: car,
                    onEdit: () => navigateToEditScreen(car),
                    onDelete: () =>
                        deleteCar(int.parse(car["id"].toString())),
                  ),
                ),
              );
            },
            onMenuSelected: (action) =>
                handleCarMenuAction(action, car),
          ),
        );
      },
    );
  }
}
