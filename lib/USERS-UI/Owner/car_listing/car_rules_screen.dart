import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_pricing_screen.dart';

class CarRulesScreen extends StatefulWidget {
  final CarListing listing;
   final String vehicleType;

  const CarRulesScreen({super.key, required this.listing,this.vehicleType = 'car',});

  @override
  State<CarRulesScreen> createState() => _CarRulesScreenState();
}

class _CarRulesScreenState extends State<CarRulesScreen> {
  final List<String> availableRules = [
    'Clean As You Go (CLAYGO)',
    'No Littering',
    'No eating or drinking inside',
    'No inter-island travel',
    'No off-roading or driving through flooded areas',
    'No pets allowed',
    'No vaping/smoking',
  ];

  late List<String> selectedRules;
  bool? hasUnlimitedMileage;

  @override
  void initState() {
    super.initState();
    selectedRules = List<String>.from(widget.listing.rules);
    hasUnlimitedMileage = widget.listing.hasUnlimitedMileage;
  }

  bool _canContinue() {
    return selectedRules.isNotEmpty && hasUnlimitedMileage != null;
  }

void _saveAndContinue() {
  if (_canContinue()) {
    widget.listing.rules = selectedRules;
    widget.listing.hasUnlimitedMileage = hasUnlimitedMileage ?? false;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CarPricingScreen(
          listing: widget.listing,
          vehicleType: widget.vehicleType, // ADD THIS LINE
        ),
      ),
    );
  } else {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text("Please select rules and mileage option."),
        backgroundColor: Colors.red,
      ),
    );
  }
}

  void _showAddRuleDialog() {
    final controller = TextEditingController();

    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Add Custom Rule', style: GoogleFonts.poppins()),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(hintText: 'Enter a rule'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              if (controller.text.trim().isNotEmpty) {
                setState(() {
                  final newRule = controller.text.trim();
                  availableRules.add(newRule);
                  if (!selectedRules.contains(newRule)) {
                    selectedRules.add(newRule);
                  }
                });
              }
              Navigator.pop(context);
            },
            child: const Text('Add'),
          ),
        ],
      ),
    );
  }

  Widget _buildMileageOption(String label, bool isSelected, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.black : Colors.white,
          border: Border.all(
            color: isSelected ? Colors.black : Colors.grey[300]!,
            width: 1.5,
          ),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
                isSelected ? Icons.check_circle : Icons.circle_outlined,
                color: isSelected ? Colors.white : Colors.grey,
                size: 20,
              ),

            const SizedBox(width: 8),
            Text(
              label,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: isSelected ? Colors.white : Colors.black,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
              ),
            ),
          ],
        ),
      ),
    );
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
                      widget.vehicleType == 'motorcycle' ? 'Your Motorcycle, Your Rules' : 'Your Cars, Your Rules',
                      style: GoogleFonts.poppins(
                        fontSize: 24,
                        fontWeight: FontWeight.w600,
                        color: Colors.black, // Also update color to black
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Create your own rules',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        color: Colors.black87,
                      ),
                    ),
                    const SizedBox(height: 32),

                    // Rules
                    Text(
                      'What are your car rules?',
                      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(height: 12),

                    ...availableRules.map(
                      (rule) => CheckboxListTile(
                        value: selectedRules.contains(rule),
                        onChanged: (value) {
                          setState(() {
                            if (value == true) {
                              selectedRules.add(rule);
                            } else {
                              selectedRules.remove(rule);
                            }
                          });
                        },
                        title: Text(rule, style: GoogleFonts.poppins(fontSize: 14)),
                        controlAffinity: ListTileControlAffinity.leading,
                        activeColor: Colors.black,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),

                    const SizedBox(height: 12),

                    OutlinedButton.icon(
                      onPressed: _showAddRuleDialog,
                      icon: const Icon(Icons.add, color: Colors.black),
                      label: Text('Add car rule', style: GoogleFonts.poppins(color: Colors.black)),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: Colors.grey[300]!),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),

                    const SizedBox(height: 32),

                    // Mileage Options
                    Text(
                      'How far can we drive your car?',
                      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(height: 12),

                    Row(
                      children: [
                        Expanded(
                          child: _buildMileageOption(
                            'Set mileage limit',
                            hasUnlimitedMileage == false,
                            () => setState(() => hasUnlimitedMileage = false),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildMileageOption(
                            'Unlimited mileage',
                            hasUnlimitedMileage == true,
                            () => setState(() => hasUnlimitedMileage = true),
                          ),
                        ),
                      ],
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
                  onPressed: _canContinue() ? _saveAndContinue : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    disabledBackgroundColor: Colors.grey[300],
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                  ),
                  child: Text(
                    'Continue',
                    style: GoogleFonts.poppins(
                      color: _canContinue() ? Colors.white : (Colors.grey[500] as Color),
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
