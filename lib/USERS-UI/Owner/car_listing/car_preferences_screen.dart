import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_features_screen.dart';

class CarPreferencesScreen extends StatefulWidget {
  final CarListing listing;
  final String vehicleType;

  const CarPreferencesScreen({
    super.key, 
    required this.listing,
    this.vehicleType = 'car',
    });

  @override
  State<CarPreferencesScreen> createState() => _CarPreferencesScreenState();
}

class _CarPreferencesScreenState extends State<CarPreferencesScreen> {
  final List<String> advanceNoticeOptions = ['30 minutes', '1 hour', '3 hours', 'Others'];
  final List<String> minDurationOptions = ['1 day', '2 days', '3 days', 'Others'];
  final List<String> maxDurationOptions = ['5 days', '1 week', '2 weeks', '1 month', '3 months', 'Others'];

  final List<Map<String, String>> deliveryOptions = [
    {
      'title': 'Guest Pickup & Guest Return',
      'subtitle': 'Simply select this option for hassle-free pickup and return. The host\'s address will be provided for this choice.',
    },
    {
      'title': 'Guest Pickup & Host Collection',
      'subtitle': 'Pickup defaults to host address. Guest can set where host should collect car.',
    },
    {
      'title': 'Host Delivery & Guest Return',
      'subtitle': 'Choose pickup location while the return is automatically the host\'s address.',
    },
    {
      'title': 'Host Delivery & Host Collection',
      'subtitle': 'Custom pickup and return locations.',
    },
  ];

  late List<String> selectedDeliveryTypes;

  @override
  void initState() {
    super.initState();
    // Fixed: removed ?? [] since deliveryTypes is already non-nullable
    selectedDeliveryTypes = List<String>.from(widget.listing.deliveryTypes);
  }

  bool _canContinue() {
    return widget.listing.advanceNotice != null &&
        widget.listing.advanceNotice!.isNotEmpty &&
        widget.listing.minTripDuration != null &&
        widget.listing.minTripDuration!.isNotEmpty &&
        widget.listing.maxTripDuration != null &&
        widget.listing.maxTripDuration!.isNotEmpty &&
        selectedDeliveryTypes.isNotEmpty;
  }

  void _continue() {
    if (_canContinue()) {
      widget.listing.deliveryTypes = selectedDeliveryTypes;

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => CarFeaturesScreen(listing: widget.listing,vehicleType: widget.vehicleType, ),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Please complete all required fields."),
          backgroundColor: Colors.red,
        ),
      );
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
                      'Tell us more about your car',
                      style: GoogleFonts.poppins(
                        fontSize: 24,
                        fontWeight: FontWeight.w600,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 32),

                    //* Advance Notice
                    Text(
                      'How much advance notice do you need before a trip starts?',
                      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
                    ),
                    Text('(1 hour recommended)', style: GoogleFonts.poppins(fontSize: 12, color: Colors.black87)),
                    const SizedBox(height: 12),
                    _buildDropdown(
                      advanceNoticeOptions,
                      widget.listing.advanceNotice,
                      (value) => setState(() => widget.listing.advanceNotice = value),
                    ),

                    const SizedBox(height: 24),

                    //* Min & Max Duration
                    Text(
                      'What\'s the shortest and longest trip possible you\'ll accept?',
                      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
                    ),

                    const SizedBox(height: 12),
                    Text('Minimum trip duration', style: GoogleFonts.poppins(fontSize: 13)),
                    const SizedBox(height: 8),
                    _buildDropdown(
                      minDurationOptions,
                      widget.listing.minTripDuration,
                      (value) => setState(() => widget.listing.minTripDuration = value),
                    ),

                    const SizedBox(height: 16),
                    Text('Maximum trip duration', style: GoogleFonts.poppins(fontSize: 13)),
                    const SizedBox(height: 8),
                    _buildDropdown(
                      maxDurationOptions,
                      widget.listing.maxTripDuration,
                      (value) => setState(() => widget.listing.maxTripDuration = value),
                    ),

                    const SizedBox(height: 24),

                    //* Delivery Type
                    Text(
                      'Prefer delivery type (can select more than 1)',
                      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(height: 12),

                    ...deliveryOptions.map((option) => _buildCheckboxTile(
                          option['title']!,
                          option['subtitle']!,
                          selectedDeliveryTypes.contains(option['title']),
                          (value) {
                            setState(() {
                              if (value == true) {
                                selectedDeliveryTypes.add(option['title']!);
                              } else {
                                selectedDeliveryTypes.remove(option['title']);
                              }
                            });
                          },
                        )),
                  ],
                ),
              ),
            ),

            //* Continue button
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
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                  ),
                 child: Text(
                    'Continue',
                    style: GoogleFonts.poppins(
                      color: _canContinue() ? Colors.white : Colors.grey[500], // fixed
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

  //* UI elements below
  Widget _buildDropdown(List<String> items, String? value, Function(String?) onChanged) {
    return Container(
      decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(12)),
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          isExpanded: true,
          hint: Text('Select', style: GoogleFonts.poppins(color: Colors.grey)),
          icon: const Icon(Icons.keyboard_arrow_down, color: Colors.black),
          items: items.map((item) => DropdownMenuItem(value: item, child: Text(item, style: GoogleFonts.poppins()))).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildCheckboxTile(String title, String subtitle, bool value, Function(bool?) onChanged) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Checkbox(value: value, onChanged: onChanged, activeColor: Colors.black),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 4),
                Text(subtitle, style: GoogleFonts.poppins(fontSize: 11, color: Colors.black87)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}