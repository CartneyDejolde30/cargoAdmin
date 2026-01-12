import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_rules_screen.dart';

class CarFeaturesScreen extends StatefulWidget {
  final CarListing listing;
  final String vehicleType;

  const CarFeaturesScreen({
    super.key, 
    required this.listing, 
    this.vehicleType = 'car',
  });

  @override
  State<CarFeaturesScreen> createState() => _CarFeaturesScreenState();
}

class _CarFeaturesScreenState extends State<CarFeaturesScreen> {
  final _descriptionController = TextEditingController();

  // Car Features
  final List<Map<String, dynamic>> carFeatures = [
    {'name': 'AUX input', 'icon': Icons.audiotrack},
    {'name': 'All-wheel drive', 'icon': Icons.all_inclusive},
    {'name': 'Android auto', 'icon': Icons.android},
    {'name': 'Apple Carplay', 'icon': Icons.apple},
    {'name': 'Autosweep', 'icon': Icons.toll},
    {'name': 'Backup camera', 'icon': Icons.videocam},
    {'name': 'Bike rack', 'icon': Icons.directions_bike},
    {'name': 'Blind spot warning', 'icon': Icons.warning},
    {'name': 'Bluetooth', 'icon': Icons.bluetooth},
    {'name': 'Child seat', 'icon': Icons.child_care},
    {'name': 'Convertible', 'icon': Icons.directions_car},
    {'name': 'Easytrip', 'icon': Icons.credit_card},
    {'name': 'GPS', 'icon': Icons.gps_fixed},
    {'name': 'Keyless entry', 'icon': Icons.vpn_key},
    {'name': 'Pet-friendly', 'icon': Icons.pets},
    {'name': 'Sunroof', 'icon': Icons.wb_sunny},
    {'name': 'USB Charger', 'icon': Icons.usb},
    {'name': 'USB input', 'icon': Icons.cable},
    {'name': 'Wheelchair accessible', 'icon': Icons.accessible},
  ];

  // Motorcycle Features
  final List<Map<String, dynamic>> motorcycleFeatures = [
    {'name': 'ABS Brakes', 'icon': Icons.settings_input_component},
    {'name': 'Traction Control', 'icon': Icons.compare_arrows},
    {'name': 'Riding Modes', 'icon': Icons.tune},
    {'name': 'Quick Shifter', 'icon': Icons.speed},
    {'name': 'Cruise Control', 'icon': Icons.near_me},
    {'name': 'Heated Grips', 'icon': Icons.local_fire_department},
    {'name': 'USB Charging Port', 'icon': Icons.usb},
    {'name': 'Center Stand', 'icon': Icons.support},
    {'name': 'Side Panniers', 'icon': Icons.work_outline},
    {'name': 'Top Box', 'icon': Icons.inventory_2},
    {'name': 'Windshield', 'icon': Icons.shield},
    {'name': 'LED Lighting', 'icon': Icons.lightbulb_outline},
    {'name': 'Digital Display', 'icon': Icons.speed},
    {'name': 'Bluetooth Connectivity', 'icon': Icons.bluetooth},
    {'name': 'Security System', 'icon': Icons.security},
    {'name': 'Passenger Backrest', 'icon': Icons.airline_seat_recline_normal},
    {'name': 'Hand Guards', 'icon': Icons.front_hand},
    {'name': 'Crash Bars', 'icon': Icons.health_and_safety},
    {'name': 'GPS Navigation', 'icon': Icons.gps_fixed},
    {'name': 'Tire Pressure Monitor', 'icon': Icons.tire_repair},
    {'name': 'Adjustable Suspension', 'icon': Icons.height},
    {'name': 'Fog Lights', 'icon': Icons.wb_twilight},
    {'name': 'Engine Guard', 'icon': Icons.shield_outlined},
    {'name': 'Saddle Bags', 'icon': Icons.shopping_bag},
  ];

  @override
  void initState() {
    super.initState();
    _descriptionController.text = widget.listing.description ?? "";
    _descriptionController.addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    super.dispose();
  }

  bool _canContinue() {
    return widget.listing.features.isNotEmpty &&
        _descriptionController.text.trim().isNotEmpty;
  }

  void _continue() {
    if (_canContinue()) {
      widget.listing.description = _descriptionController.text.trim();

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => CarRulesScreen(
            listing: widget.listing,
            vehicleType: widget.vehicleType,
          ),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.vehicleType == 'motorcycle'
                ? "Please add a description and select at least 1 feature."
                : "Please add a description and select at least 1 feature."
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  List<Map<String, dynamic>> get _currentFeatures {
    return widget.vehicleType == 'motorcycle' ? motorcycleFeatures : carFeatures;
  }

  @override
  Widget build(BuildContext context) {
    final isMoto = widget.vehicleType == 'motorcycle';
    
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isMoto
                          ? 'Let guests know what your motorcycle has to offer'
                          : 'Let guests know what your car has to offer',
                      style: GoogleFonts.poppins(
                        fontSize: 22,
                        fontWeight: FontWeight.w600,
                        color: Colors.grey[700],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Description Section
                    Text(
                      isMoto
                          ? 'What makes your motorcycle special?'
                          : 'What makes your car special?',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 12),

                    TextField(
                      controller: _descriptionController,
                      maxLines: 4,
                      style: GoogleFonts.poppins(fontSize: 14),
                      decoration: InputDecoration(
                        hintText: isMoto
                            ? 'Describe your amazing motorcycle...'
                            : 'Describe your amazing car...',
                        hintStyle: GoogleFonts.poppins(color: Colors.grey[400]),
                        filled: true,
                        fillColor: Colors.grey[100],
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.all(16),
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Features Section
                    Text(
                      isMoto ? 'Your motorcycle\'s features' : 'Your car\'s features',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 12),

                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 3,
                        childAspectRatio: 0.9,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                      ),
                      itemCount: _currentFeatures.length,
                      itemBuilder: (context, index) {
                        final feature = _currentFeatures[index];
                        final isSelected = widget.listing.features.contains(feature['name']);

                        return GestureDetector(
                          onTap: () {
                            setState(() {
                              if (isSelected) {
                                widget.listing.features.remove(feature['name']);
                              } else {
                                widget.listing.features.add(feature['name']);
                              }
                            });
                          },
                          child: Container(
                            decoration: BoxDecoration(
                              color: isSelected ? Colors.black : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isSelected ? Colors.black : Colors.grey[300]!,
                                width: 1.5,
                              ),
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  feature['icon'],
                                  size: 32,
                                  color: isSelected ? Colors.white : Colors.grey[700],
                                ),
                                const SizedBox(height: 8),
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 4),
                                  child: Text(
                                    feature['name'],
                                    textAlign: TextAlign.center,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: GoogleFonts.poppins(
                                      fontSize: 11,
                                      color: isSelected ? Colors.white : Colors.grey[800],
                                      fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),

            // Continue Button
            Padding(
              padding: const EdgeInsets.all(24),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _canContinue() ? _continue : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    disabledBackgroundColor: Colors.grey[300],
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: Text(
                    'Continue',
                    style: GoogleFonts.poppins(
                      color: _canContinue() ? const Color(0xFFCDFE3D) : Colors.grey[500],
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}