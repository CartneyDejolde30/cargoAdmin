import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../bookings/pricing/pricing_calculator.dart';
import 'map_route_screen.dart'; 
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'gcash_payment_screen.dart';

class BookingScreen extends StatefulWidget {
  final int carId;
  final String carName;
  final String carImage;
  final String pricePerDay;
  final String location;
  final String ownerId;
  final String? userId;
  final String? userFullName;
  final String? userEmail;
  final String? userContact;
  final String? userMunicipality;
  final double? ownerLatitude;
  final double? ownerLongitude;

  const BookingScreen({
    super.key,
    required this.carId,
    required this.carName,
    required this.carImage,
    required this.pricePerDay,
    required this.ownerId,
    required this.location,
    this.userId,
    this.userFullName,
    this.userContact,
    this.userEmail,
    this.userMunicipality,
    this.ownerLatitude,
    this.ownerLongitude,
  });

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  // Verification states
  bool isCheckingVerification = true;
  bool isVerifiedUser = false;
  String? verificationError;
  String debugInfo = "Initializing...";
  
  int currentStep = 0;
  bool needsDelivery = false;
  String selectedPeriod = 'Day';

  // Controllers
  final TextEditingController fullNameController = TextEditingController();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController contactController = TextEditingController();

  // Dates
  DateTime? pickupDate;
  DateTime? returnDate;
  TimeOfDay pickupTime = TimeOfDay(hour: 9, minute: 0);
  TimeOfDay returnTime = TimeOfDay(hour: 17, minute: 0);
  
  BookingPriceBreakdown? priceBreakdown;

  double get basePrice => double.tryParse(widget.pricePerDay) ?? 0;

  int get numberOfDays {
    if (pickupDate == null || returnDate == null) return 1;
    return returnDate!.difference(pickupDate!).inDays + 1;
  }

  @override
  void initState() {
    super.initState();
    _checkVerificationOnInit();
    
    // Auto-fill user data from profile
    if (widget.userFullName != null && widget.userFullName!.isNotEmpty) {
      fullNameController.text = widget.userFullName!;
    }
    if (widget.userEmail != null && widget.userEmail!.isNotEmpty) {
      emailController.text = widget.userEmail!;
    }
    if (widget.userContact != null && widget.userContact!.isNotEmpty) {
      contactController.text = widget.userContact!;
    }
    _calculatePrice();
  }

  @override
  void dispose() {
    fullNameController.dispose();
    emailController.dispose();
    contactController.dispose();
    super.dispose();
  }

  void _calculatePrice() {
    setState(() {
      priceBreakdown = PricingCalculator.calculatePrice(
        pricePerDay: basePrice,
        numberOfDays: numberOfDays,
        withDriver: false,
        rentalPeriod: selectedPeriod,
        needsDelivery: needsDelivery,
        deliveryDistance: 5.0,
        includeInsurance: false,
      );
    });
  }

  // Replace your _checkVerificationOnInit() method with this improved version:

Future<void> _checkVerificationOnInit() async {
  print("🔍 Starting verification check...");
  
  if (widget.userId == null || widget.userId!.isEmpty) {
    print("❌ No user ID provided");
    setState(() {
      isCheckingVerification = false;
      isVerifiedUser = false;
      verificationError = 'User not logged in';
    });
    return;
  }

  print("✅ User ID: ${widget.userId}");

  try {
    final url = Uri.parse(
      "http://10.139.150.2/carGOAdmin/api/check_verification.php?user_id=${widget.userId}"
    );
    
    print("📡 Calling API: $url");
    
    final response = await http.get(url).timeout(
      Duration(seconds: 10),
      onTimeout: () {
        throw Exception('Connection timeout');
      },
    );

    print("📥 Response Status: ${response.statusCode}");
    print("📥 Response Body: ${response.body}");

    if (response.statusCode == 200) {
      final result = jsonDecode(response.body);
      
      // Debug: Print the exact response
      print("🔍 Parsed JSON: $result");
      print("🔍 is_verified value: ${result['is_verified']}");
      print("🔍 is_verified type: ${result['is_verified'].runtimeType}");
      print("🔍 can_add_car value: ${result['can_add_car']}");
      print("🔍 Message: ${result['message']}");
      
      // Handle the response properly
      final isVerified = result['is_verified'];
      final canAddCar = result['can_add_car'];
      final message = result['message'] ?? 'Unknown status';
      
      setState(() {
        // Handle both boolean and string values
        isVerifiedUser = (isVerified == true || isVerified == 1 || isVerified == "1" || isVerified == "true") &&
                        (canAddCar == true || canAddCar == 1 || canAddCar == "1" || canAddCar == "true");
        isCheckingVerification = false;
        
        if (!isVerifiedUser) {
          verificationError = message;
          print("❌ User not verified: $message");
        } else {
          print("✅ User is verified!");
        }
      });
    } else {
      print("❌ HTTP Error: ${response.statusCode}");
      setState(() {
        isCheckingVerification = false;
        isVerifiedUser = false;
        verificationError = 'Server error: ${response.statusCode}';
      });
    }
  } catch (e) {
    print("❌ Exception caught: $e");
    print("❌ Exception type: ${e.runtimeType}");
    
    setState(() {
      isCheckingVerification = false;
      isVerifiedUser = false;
      verificationError = 'Connection failed: ${e.toString()}';
    });
  }
}

  Future<void> _openMapDirections() async {
    if (widget.ownerLatitude != null && widget.ownerLongitude != null) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => MapRouteScreen(
            destinationLat: widget.ownerLatitude!,
            destinationLng: widget.ownerLongitude!,
            locationName: widget.location,
            carName: widget.carName,
          ),
        ),
      );
    } else {
      final searchUrl = Uri.parse(
        'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(widget.location)}'
      );
      
      if (await canLaunchUrl(searchUrl)) {
        await launchUrl(searchUrl, mode: LaunchMode.externalApplication);
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Unable to open maps application'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  Future<void> _selectDate(BuildContext context, bool isPickup) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.black,
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        if (isPickup) {
          pickupDate = picked;
          if (returnDate == null || returnDate!.isBefore(picked)) {
            returnDate = picked.add(Duration(days: 1));
          }
        } else {
          returnDate = picked;
        }
        _calculatePrice();
      });
    }
  }

  Future<void> _selectTime(BuildContext context, bool isPickup) async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: isPickup ? pickupTime : returnTime,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.black,
              onPrimary: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        if (isPickup) {
          pickupTime = picked;
        } else {
          returnTime = picked;
        }
      });
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
          icon: Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Booking Details',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
  // Show loading state while checking verification
  if (isCheckingVerification) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(color: Colors.black),
          SizedBox(height: 16),
          Text(
            'Verifying account...',
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
          ),
          SizedBox(height: 8),
          Text(
            debugInfo,
            style: GoogleFonts.poppins(
              fontSize: 10,
              color: Colors.grey.shade400,
            ),
          ),
        ],
      ),
    );
  }

  // Show error state if not verified
  if (!isVerifiedUser) {
    return Center(
      child: SingleChildScrollView(
        padding: EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.block,
                color: Colors.red.shade600,
                size: 64,
              ),
            ),
            SizedBox(height: 24),
            Text(
              'Access Denied',
              style: GoogleFonts.poppins(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 12),
            Text(
              'Your account is not verified.',
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
            SizedBox(height: 16),
            // DEBUG INFO
            Container(
              width: double.infinity,
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.black87,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '🔍 DEBUG',
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Colors.yellow,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    debugInfo,
                    style: GoogleFonts.poppins(
                      fontSize: 9,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    'isVerified: $isVerifiedUser',
                    style: GoogleFonts.poppins(
                      fontSize: 9,
                      color: Colors.white70,
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => Navigator.pop(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
                padding: EdgeInsets.symmetric(horizontal: 32, vertical: 14),
              ),
              child: Text(
                'Go Back',
                style: GoogleFonts.poppins(color: Colors.white),
              ),
            ),
            TextButton(
              onPressed: () {
                setState(() {
                  isCheckingVerification = true;
                });
                _checkVerificationOnInit();
              },
              child: Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }

  // ✅ VERIFIED - Show booking form
  return Column(
    children: [
      // Success indicator
      Container(
        padding: EdgeInsets.all(12),
        color: Colors.green.shade50,
        child: Row(
          children: [
            Icon(Icons.verified, color: Colors.green.shade700, size: 20),
            SizedBox(width: 8),
            Text(
              'Account Verified ✓',
              style: GoogleFonts.poppins(
                fontSize: 12,
                color: Colors.green.shade700,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
      Expanded(
        child: SingleChildScrollView(
          padding: EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildCarInfoCard(),
              SizedBox(height: 24),
              _buildDeliveryToggle(),
              SizedBox(height: 24),
              Text(
                'Renter Information',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 12),
              _buildTextField(
                controller: fullNameController,
                label: 'Full Name',
                icon: Icons.person_outline,
                hint: 'Enter your full name',
              ),
              SizedBox(height: 16),
              _buildTextField(
                controller: emailController,
                label: 'Email Address',
                icon: Icons.email_outlined,
                hint: 'Enter your email',
                keyboardType: TextInputType.emailAddress,
              ),
              SizedBox(height: 16),
              _buildTextField(
                controller: contactController,
                label: 'Contact Number',
                icon: Icons.phone_outlined,
                hint: 'e.g., 09XX XXX XXXX',
                keyboardType: TextInputType.phone,
              ),
              SizedBox(height: 24),
              Text(
                'Rental Period',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 12),
              _buildPeriodSelector(),
              SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    child: _buildDatePicker(
                      label: 'Pick up Date',
                      date: pickupDate,
                      onTap: () => _selectDate(context, true),
                    ),
                  ),
                  SizedBox(width: 12),
                  Expanded(
                    child: _buildDatePicker(
                      label: 'Return Date',
                      date: returnDate,
                      onTap: () => _selectDate(context, false),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _buildTimePicker(
                      label: 'Pick up Time',
                      time: pickupTime,
                      onTap: () => _selectTime(context, true),
                    ),
                  ),
                  SizedBox(width: 12),
                  Expanded(
                    child: _buildTimePicker(
                      label: 'Return Time',
                      time: returnTime,
                      onTap: () => _selectTime(context, false),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 24),
              Text(
                'Car Location',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 12),
              _buildLocationWithMap(),
              SizedBox(height: 24),
              _buildPriceBreakdown(),
              SizedBox(height: 100),
            ],
          ),
        ),
      ),
      _buildBottomButton(),
    ],
  );
}

  // ... [Keep all your existing widget building methods unchanged]
  // _buildCarInfoCard, _buildDeliveryToggle, _buildTextField, etc.
  
  Widget _buildCarInfoCard() {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Image.network(
              widget.carImage,
              width: 80,
              height: 60,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                width: 80,
                height: 60,
                color: Colors.grey.shade300,
                child: Icon(Icons.directions_car),
              ),
            ),
          ),
          SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.carName,
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.location_on, size: 14, color: Colors.grey.shade600),
                    SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        widget.location,
                        style: GoogleFonts.poppins(
                          fontSize: 12,
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
        ],
      ),
    );
  }

  Widget _buildDeliveryToggle() {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Car Delivery',
                  style: GoogleFonts.poppins(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Base ₱${PricingCalculator.deliveryFeeBase.toStringAsFixed(0)} + ₱${PricingCalculator.deliveryFeePerKm}/km',
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    color: Colors.orange.shade700,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: needsDelivery,
            onChanged: (value) {
              setState(() {
                needsDelivery = value;
                _calculatePrice();
              });
            },
            activeThumbColor: Colors.black,
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    required String hint,
    TextInputType? keyboardType,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.grey.shade700,
          ),
        ),
        SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: keyboardType,
          style: GoogleFonts.poppins(fontSize: 14),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.poppins(
              color: Colors.grey.shade400,
              fontSize: 14,
            ),
            prefixIcon: Icon(icon, color: Colors.grey.shade400, size: 20),
            filled: true,
            fillColor: Colors.grey.shade50,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.black, width: 1.5),
            ),
            contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          ),
        ),
      ],
    );
  }

  Widget _buildPeriodSelector() {
    return Row(
      children: [
        _buildPeriodOption('Day'),
        SizedBox(width: 12),
        _buildPeriodOption('Weekly'),
        SizedBox(width: 12),
        _buildPeriodOption('Monthly'),
      ],
    );
  }

  Widget _buildPeriodOption(String period) {
    bool isSelected = selectedPeriod == period;
    String discountText = '';
    
    if (period == 'Weekly') {
      discountText = ' (${(PricingCalculator.weeklyDiscountRate * 100).toStringAsFixed(0)}% off)';
    } else if (period == 'Monthly') {
      discountText = ' (${(PricingCalculator.monthlyDiscountRate * 100).toStringAsFixed(0)}% off)';
    }
    
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() {
            selectedPeriod = period;
            _calculatePrice();
          });
        },
        child: Container(
          padding: EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: isSelected ? Colors.black : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isSelected ? Colors.black : Colors.grey.shade300,
            ),
          ),
          child: Column(
            children: [
              Text(
                period,
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: isSelected ? Colors.white : Colors.grey.shade600,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                ),
              ),
              if (discountText.isNotEmpty)
                Text(
                  discountText,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(
                    fontSize: 9,
                    color: isSelected ? Colors.green.shade300 : Colors.green.shade700,
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDatePicker({
    required String label,
    required DateTime? date,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Colors.grey.shade700,
            ),
          ),
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 14),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.calendar_today, size: 18, color: Colors.grey.shade600),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    date != null
                        ? DateFormat('dd MMMM yyyy').format(date)
                        : 'Select date',
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: date != null ? Colors.black : Colors.grey.shade400,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTimePicker({
    required String label,
    required TimeOfDay time,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Colors.grey.shade700,
            ),
          ),
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 14),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.access_time, size: 18, color: Colors.grey.shade600),
                SizedBox(width: 8),
                Text(
                  time.format(context),
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    color: Colors.black,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLocationWithMap() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        children: [
          Padding(
            padding: EdgeInsets.all(16),
            child: Row(
              children: [
                Icon(Icons.location_on, color: Colors.red, size: 20),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    widget.location,
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      color: Colors.black87,
                    ),
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: Colors.grey.shade200),
          InkWell(
            onTap: _openMapDirections,
            borderRadius: BorderRadius.only(
              bottomLeft: Radius.circular(12),
              bottomRight: Radius.circular(12),
            ),
            child: Container(
              padding: EdgeInsets.symmetric(vertical: 14),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.directions, color: Colors.blue.shade700, size: 20),
                  SizedBox(width: 8),
                  Text(
                    'View Route on Map',
                    style: GoogleFonts.poppins(
                      fontSize: 13,
                      color: Colors.blue.shade700,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPriceBreakdown() {
    if (priceBreakdown == null) return SizedBox();

    return Container(
      padding: EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Price Breakdown',
            style: GoogleFonts.poppins(
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 16),
          
          _buildBreakdownRow(
            'Base Rental',
            '${PricingCalculator.formatCurrency(priceBreakdown!.baseRental)}',
            subtitle: '₱${priceBreakdown!.pricePerDay.toStringAsFixed(0)} × ${priceBreakdown!.numberOfDays} days',
          ),
          
          if (priceBreakdown!.discount > 0)
            _buildBreakdownRow(
              '${selectedPeriod} Discount',
              '-${PricingCalculator.formatCurrency(priceBreakdown!.discount)}',
              isDiscount: true,
              subtitle: '${priceBreakdown!.discountPercentage.toStringAsFixed(1)}% off',
            ),
          
          if (needsDelivery)
            _buildBreakdownRow(
              'Delivery Fee',
              PricingCalculator.formatCurrency(priceBreakdown!.deliveryFee),
            ),
          
          _buildBreakdownRow(
            'Service Fee',
            PricingCalculator.formatCurrency(priceBreakdown!.serviceFee),
            subtitle: '5% platform fee',
          ),
          
          Divider(height: 24, thickness: 1.5),
          
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Total Amount',
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                PricingCalculator.formatCurrency(priceBreakdown!.totalAmount),
                style: GoogleFonts.poppins(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.green.shade700,
                ),
              ),
            ],
          ),
          
          SizedBox(height: 8),
          Text(
            'Effective rate: ${PricingCalculator.formatCurrency(priceBreakdown!.effectiveDailyRate)}/day',
            style: GoogleFonts.poppins(
              fontSize: 11,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBreakdownRow(
    String label,
    String amount, {
    String? subtitle,
    bool isDiscount = false,
  }) {
    return Padding(
      padding: EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
              Text(
                amount,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDiscount ? Colors.green.shade700 : Colors.black,
                ),
              ),
            ],
          ),
          if (subtitle != null)
            Padding(
              padding: EdgeInsets.only(top: 2),
              child: Text(
                subtitle,
                style: GoogleFonts.poppins(
                  fontSize: 11,
                  color: Colors.grey.shade600,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildBottomButton() {
    return Container(
      padding: EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: Offset(0, -5),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: ElevatedButton(
          onPressed: () {
            if (_validateForm()) {
              _proceedToPayment();
            }
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.black,
            padding: EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            elevation: 0,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                priceBreakdown != null 
                    ? PricingCalculator.formatCurrency(priceBreakdown!.totalAmount)
                    : '₱0.00',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                '  •  ',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 16,
                ),
              ),
              Text(
                'Pay Now',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  bool _validateForm() {
    if (fullNameController.text.trim().isEmpty) {
      _showError('Please enter your full name');
      return false;
    }
    if (emailController.text.trim().isEmpty) {
      _showError('Please enter your email');
      return false;
    }
    if (!_isValidEmail(emailController.text.trim())) {
      _showError('Please enter a valid email address');
      return false;
    }
    if (contactController.text.trim().isEmpty) {
      _showError('Please enter your contact number');
      return false;
    }
    if (pickupDate == null) {
      _showError('Please select pickup date');
      return false;
    }
    if (returnDate == null) {
      _showError('Please select return date');
      return false;
    }
    return true;
  }

  bool _isValidEmail(String email) {
    return RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email);
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        margin: EdgeInsets.all(16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }

  void _proceedToPayment() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green.shade600, size: 28),
            SizedBox(width: 12),
            Text(
              'Confirm Booking',
              style: GoogleFonts.poppins(
                fontWeight: FontWeight.bold,
                fontSize: 18,
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildSummaryRow('Car', widget.carName),
              _buildSummaryRow('Rental Period', selectedPeriod),
              _buildSummaryRow('Duration', '$numberOfDays day${numberOfDays > 1 ? "s" : ""}'),
              _buildSummaryRow(
                'Pickup',
                pickupDate != null
                    ? '${DateFormat('MMM dd, yyyy').format(pickupDate!)} at ${pickupTime.format(context)}'
                    : 'Not set',
              ),
              _buildSummaryRow(
                'Return',
                returnDate != null
                    ? '${DateFormat('MMM dd, yyyy').format(returnDate!)} at ${returnTime.format(context)}'
                    : 'Not set',
              ),
              _buildSummaryRow('Delivery', needsDelivery ? 'Yes' : 'No'),
              _buildSummaryRow('Location', widget.location),
              Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Total Amount',
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  Text(
                    PricingCalculator.formatCurrency(priceBreakdown!.totalAmount),
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                      color: Colors.green.shade700,
                    ),
                  ),
                ],
              ),
              SizedBox(height: 16),
              Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.payment, color: Colors.blue.shade700, size: 20),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'You will proceed to GCash payment to complete your booking.',
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: Colors.blue.shade900,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(
              'Cancel',
              style: GoogleFonts.poppins(
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _processPayment();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.black,
              padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              'Proceed to Payment',
              style: GoogleFonts.poppins(
                color: Colors.white,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.grey.shade600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.poppins(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: Colors.black87,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _processPayment() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => Center(
        child: Container(
          padding: EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: Colors.black),
              SizedBox(height: 16),
              Text(
                'Processing booking...',
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );

    _submitBookingToServer();
  }

 // Replace your _submitBookingToServer() method with this:

// Replace your _submitBookingToServer() method with this:

Future<void> _submitBookingToServer() async {
  final url = Uri.parse("http://10.139.150.2/carGOAdmin/api/create_booking.php");

  // Validate user_id
  if (widget.userId == null || widget.userId!.isEmpty) {
    Navigator.pop(context);
    _showError("User session expired. Please login again.");
    return;
  }

  try {
    print("🚀 Submitting booking...");
    print("📤 User ID: ${widget.userId}");
    print("📤 Car ID: ${widget.carId}");
    print("📤 Owner ID: ${widget.ownerId}");
    
    final requestBody = {
      "user_id": widget.userId!.toString(),
      "car_id": widget.carId.toString(),
      "owner_id": widget.ownerId.toString(),
      "full_name": fullNameController.text.trim(),
      "email": emailController.text.trim(),
      "contact": contactController.text.trim(),
      "pickup_date": DateFormat('yyyy-MM-dd').format(pickupDate!),
      "return_date": DateFormat('yyyy-MM-dd').format(returnDate!),
      "pickup_time": pickupTime.format(context),
      "return_time": returnTime.format(context),
      "rental_period": selectedPeriod,
      "needs_delivery": needsDelivery ? "1" : "0",
      "total_amount": priceBreakdown!.totalAmount.toStringAsFixed(2),
      "payment_method": "gcash",
    };

    print("📤 Request body: $requestBody");

    final response = await http.post(
      url,
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: requestBody,
    ).timeout(
      Duration(seconds: 15),
      onTimeout: () {
        throw Exception('Request timeout');
      },
    );

    print("📥 Response status: ${response.statusCode}");
    print("📥 Response body: ${response.body}");

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);

      if (data["success"] == true) {
        // PayMongo integration response
        final bookingId = data["data"]["booking_id"] as int;
        final paymentIntentId = data["data"]["payment_intent_id"] as String?;
        final clientKey = data["data"]["client_key"] as String?;
        final totalAmount = (data["data"]["total_amount"] as num).toDouble();
        
        print("✅ Booking created! ID: $bookingId");
        print("💳 Payment Intent: $paymentIntentId");

        Navigator.pop(context); // Close loading dialog

        // Navigate to payment screen with PayMongo details
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => GCashPaymentScreen(
              bookingId: bookingId, // Now passing as int
              carId: widget.carId,
              carName: widget.carName,
              carImage: widget.carImage,
              ownerId: widget.ownerId,
              userId: widget.userId!,
              fullName: fullNameController.text.trim(),
              email: emailController.text.trim(),
              contact: contactController.text.trim(),
              pickupDate: DateFormat('yyyy-MM-dd').format(pickupDate!),
              returnDate: DateFormat('yyyy-MM-dd').format(returnDate!),
              pickupTime: pickupTime.format(context),
              returnTime: returnTime.format(context),
              rentalPeriod: selectedPeriod,
              needsDelivery: needsDelivery,
              totalAmount: totalAmount,
              paymentIntentId: paymentIntentId,
              clientKey: clientKey,
            ),
          ),
        );
      } else {
        Navigator.pop(context);
        String errorMsg = data["message"] ?? "Booking failed";
        print("❌ Booking failed: $errorMsg");
        
        // Better error handling
        if (errorMsg.toLowerCase().contains("unauthorized")) {
          _showError("Your account is not authorized. Please verify your account.");
        } else if (errorMsg.toLowerCase().contains("not verified")) {
          _showError("Your account is not verified. Please complete verification first.");
        } else if (errorMsg.toLowerCase().contains("not available")) {
          _showError("This car is no longer available.");
        } else {
          _showError(errorMsg);
        }
      }
    } else if (response.statusCode == 401) {
      Navigator.pop(context);
      _showError("Unauthorized: Please log in again.");
    } else {
      Navigator.pop(context);
      _showError("Server error (${response.statusCode})");
    }
  } catch (e) {
    Navigator.pop(context);
    print("❌ Exception: $e");
    
    if (e.toString().contains('timeout')) {
      _showError("Connection timeout. Please check your internet.");
    } else {
      _showError("Connection error: ${e.toString()}");
    }
  }
}
}