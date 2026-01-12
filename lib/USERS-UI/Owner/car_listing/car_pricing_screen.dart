import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_location_screen.dart';

class CarPricingScreen extends StatefulWidget {
  final CarListing listing;
  final String vehicleType;

  const CarPricingScreen({super.key, required this.listing,this.vehicleType = 'car',});

  @override
  State<CarPricingScreen> createState() => _CarPricingScreenState();
}

class _CarPricingScreenState extends State<CarPricingScreen> {
  final _priceController = TextEditingController();

  @override
  void initState() {
    super.initState();

    // Load existing value if editing
    if (widget.listing.dailyRate != null && widget.listing.dailyRate! > 0) {
      _priceController.text = widget.listing.dailyRate!.toStringAsFixed(2);
    }

    _priceController.addListener(() {
      double? value = double.tryParse(_priceController.text);
      setState(() {
        widget.listing.dailyRate = (value != null && value > 0) ? value : null;
      });
    });
  }

  bool get _canContinue {
    return widget.listing.dailyRate != null && widget.listing.dailyRate! > 49;
  }

void _continue() {
  if (_canContinue) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => CarLocationScreen(
          listing: widget.listing,
          vehicleType: widget.vehicleType, // ADD THIS LINE
        ),
      ),
    );
  } else {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text("Please enter a valid rental price above ₱50."),
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
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.vehicleType == 'motorcycle' 
                        ? 'How much would you want your motorcycle to be rented per day?'
                        : 'How much would you want your car to be rented per day?',
                      style: GoogleFonts.poppins(
                        fontSize: 22,
                        fontWeight: FontWeight.w600,
                        color: Colors.black, // Update to black
                      ),
                    ),

                    const SizedBox(height: 40),

                    Center(
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            '₱',
                            style: GoogleFonts.poppins(
                              fontSize: 48,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(width: 10),

                          // PRICE FIELD
                          IntrinsicWidth(
                            child: TextField(
                              controller: _priceController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              inputFormatters: [
                                FilteringTextInputFormatter.allow(
                                  RegExp(r'^\d+\.?\d{0,2}$'),
                                )
                              ],
                              style: GoogleFonts.poppins(
                                fontSize: 48,
                                fontWeight: FontWeight.w600,
                              ),
                              textAlign: TextAlign.center,
                              decoration: InputDecoration(
                                hintText: '00.00',
                                hintStyle: GoogleFonts.poppins(
                                  fontSize: 48,
                                  color: Colors.grey[300],
                                ),
                                border: InputBorder.none,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 8),

                    Center(
                      child: Text(
                        "Minimum recommended: ₱50 / day",
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: Colors.black87,
                        ),
                      ),
                    ),

                    const Spacer(),
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
                  onPressed: _canContinue ? _continue : null,
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
                      color: _canContinue ? Colors.white : Colors.grey[500],
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
