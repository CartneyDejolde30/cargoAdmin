import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/user_verification.dart';
import 'package:flutter_application_1/USERS-UI/Owner/verification/id_upload_screen.dart';

class PersonalInfoScreen extends StatefulWidget {
  final UserVerification? existingData;

  const PersonalInfoScreen({Key? key, this.existingData}) : super(key: key);

  @override
  State<PersonalInfoScreen> createState() => _PersonalInfoScreenState();


  
}

class _PersonalInfoScreenState extends State<PersonalInfoScreen> {
  late UserVerification verification;

  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _mobileController = TextEditingController();

  final _firstNameFocus = FocusNode();
  final _lastNameFocus = FocusNode();
  final _emailFocus = FocusNode();
  final _mobileFocus = FocusNode();

  final List<String> genders = ['Male', 'Female'];

  // Agusan del Sur municipalities
  final List<String> municipalities = [
    'Bayugan',
    'Bunawan',
    'Esperanza',
    'La Paz',
    'Loreto',
    'Prosperidad',
    'Rosario',
    'San Francisco',
    'San Luis',
    'Santa Josefa',
    'Sibagat',
    'Talacogon',
    'Trento',
    'Veruela',
  ];

  Map<String, dynamic> barangaysData = {};
  List<String> barangays = [];

Future<void> _loadUserId() async {
  final prefs = await SharedPreferences.getInstance();
  print("USER ID FROM PREFS = ${prefs.getString("user_id")}");

  setState(() {
    verification.userId = int.tryParse(prefs.getString("user_id") ?? "0");
  });
}


  @override
  void initState() {
    super.initState();

    verification = widget.existingData ?? UserVerification();

    _loadUserId();


    _firstNameController.text = verification.firstName ?? '';
    _lastNameController.text = verification.lastName ?? '';
    _emailController.text = verification.email ?? '';
    _mobileController.text = verification.mobileNumber ?? '';

    // Set default values for region and province
    verification.permRegion = 'Region XIII (Caraga)';
    verification.permProvince = 'Agusan del Sur';

    _loadBarangaysData();

    // Add listeners for real-time validation
    _firstNameController.addListener(() => setState(() {}));
    _lastNameController.addListener(() => setState(() {}));
    _emailController.addListener(() => setState(() {}));
    _mobileController.addListener(() => setState(() {}));
  }

  Future<void> _loadBarangaysData() async {
    final response = await rootBundle.loadString('assets/data/caraga.json');
    final data = json.decode(response);

    setState(() {
      // Extract Agusan del Sur data
      if (data.containsKey('Agusan del Sur')) {
        barangaysData = data['Agusan del Sur'];
      }

      // Load barangays if municipality is already selected
      if (verification.permCity != null && 
          barangaysData.containsKey(verification.permCity)) {
        barangays = List<String>.from(barangaysData[verification.permCity]);
      }
    });
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _mobileController.dispose();
    _firstNameFocus.dispose();
    _lastNameFocus.dispose();
    _emailFocus.dispose();
    _mobileFocus.dispose();
    super.dispose();
  }

  bool _canContinue() {
    return (verification.firstName?.isNotEmpty ?? false) &&
        (verification.lastName?.isNotEmpty ?? false) &&
        verification.permCity != null &&
        verification.permBarangay != null &&
        verification.email != null &&
        verification.email!.isNotEmpty &&
        verification.mobileNumber != null &&
        verification.mobileNumber!.isNotEmpty &&
        verification.gender != null &&
        verification.dateOfBirth != null &&
        _isValidEmail(verification.email!) &&
        _isValidPhone(verification.mobileNumber!);
  }

  bool _isValidEmail(String email) {
    return RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email);
  }

  bool _isValidPhone(String phone) {
    return RegExp(r'^[0-9]{11}$').hasMatch(phone);
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.error_outline, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: Colors.red.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _submit() async {
  final prefs = await SharedPreferences.getInstance();
  verification.userId = int.tryParse(prefs.getString("user_id") ?? "0");

  verification.firstName = _firstNameController.text;
  verification.lastName = _lastNameController.text;
  verification.email = _emailController.text;
  verification.mobileNumber = _mobileController.text;

  if (!_isValidEmail(verification.email!)) {
    _showError("Please enter a valid email address");
    return;
  }

  if (!_isValidPhone(verification.mobileNumber!)) {
    _showError("Mobile number must be exactly 11 digits");
    return;
  }

  Navigator.push(
    context,
    MaterialPageRoute(
      builder: (context) => IDUploadScreen(verification: verification),
    ),
  );
}


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.arrow_back, color: Colors.black, size: 20),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Account Verification',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontWeight: FontWeight.w600,
            fontSize: 18,
          ),
        ),
        centerTitle: true,
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Progress Indicator
            Container(
              padding: const EdgeInsets.all(24),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      _buildProgressStep(1, "Personal", true, false),
                      Expanded(child: _buildProgressLine(false)),
                      _buildProgressStep(2, "ID Upload", false, false),
                      Expanded(child: _buildProgressLine(false)),
                      _buildProgressStep(3, "Selfie", false, false),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade100),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline, color: Colors.blue.shade700, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'Verification takes 24-48 hours to process',
                            style: GoogleFonts.poppins(
                              fontSize: 12,
                              color: Colors.blue.shade900,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Section Header
                    _buildSectionHeader(
                      icon: Icons.person_outline,
                      title: 'Personal Information',
                      subtitle: 'Please provide your accurate details',
                    ),
                    const SizedBox(height: 24),

                    // Name Fields
                    Row(
                      children: [
                        Expanded(
                          child: _buildTextField(
                            'First Name',
                            _firstNameController,
                            _firstNameFocus,
                            icon: Icons.badge_outlined,
                            onChanged: (v) => verification.firstName = v,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: _buildTextField(
                            'Last Name',
                            _lastNameController,
                            _lastNameFocus,
                            icon: Icons.badge_outlined,
                            onChanged: (v) => verification.lastName = v,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Address Section
                    _buildSectionHeader(
                      icon: Icons.location_on_outlined,
                      title: 'Address in Agusan del Sur',
                      subtitle: 'Based on your government ID',
                    ),
                    const SizedBox(height: 16),

                    // Location Info Card
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.location_city, color: Colors.green.shade700, size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Agusan del Sur, CARAGA Region',
                                  style: GoogleFonts.poppins(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.green.shade900,
                                  ),
                                ),
                                Text(
                                  'Select your municipality and barangay',
                                  style: GoogleFonts.poppins(
                                    fontSize: 11,
                                    color: Colors.green.shade700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    _buildDropdown(
                      'Municipality',
                      municipalities,
                      verification.permCity,
                      Icons.location_city,
                      (v) {
                        setState(() {
                          verification.permCity = v;
                          barangays = v != null && barangaysData.containsKey(v)
                              ? List<String>.from(barangaysData[v])
                              : [];
                          verification.permBarangay = null;
                        });
                      },
                    ),
                    const SizedBox(height: 16),

                    if (barangays.isNotEmpty) ...[
                      _buildDropdown(
                        'Barangay',
                        barangays,
                        verification.permBarangay,
                        Icons.home_work_outlined,
                        (v) => setState(() => verification.permBarangay = v),
                      ),
                      const SizedBox(height: 24),
                    ],

                    // Contact Section
                    _buildSectionHeader(
                      icon: Icons.contact_phone_outlined,
                      title: 'Contact Information',
                      subtitle: 'We\'ll use this to reach you',
                    ),
                    const SizedBox(height: 16),

                    _buildTextField(
                      'Email Address',
                      _emailController,
                      _emailFocus,
                      icon: Icons.email_outlined,
                      keyboardType: TextInputType.emailAddress,
                      onChanged: (v) => verification.email = v,
                      validator: (v) => _isValidEmail(v ?? '') ? null : 'Invalid email',
                    ),
                    const SizedBox(height: 16),

                    _buildTextField(
                      'Mobile Number',
                      _mobileController,
                      _mobileFocus,
                      icon: Icons.phone_outlined,
                      keyboardType: TextInputType.phone,
                      hint: 'e.g. 09123456789',
                      onChanged: (v) => verification.mobileNumber = v,
                      validator: (v) => _isValidPhone(v ?? '') ? null : '11 digits required',
                    ),
                    const SizedBox(height: 24),

                    // Personal Details Section
                    _buildSectionHeader(
                      icon: Icons.info_outlined,
                      title: 'Personal Details',
                      subtitle: 'Additional information',
                    ),
                    const SizedBox(height: 16),

                    _buildDropdown(
                      'Gender',
                      genders,
                      verification.gender,
                      Icons.wc,
                      (v) => setState(() => verification.gender = v),
                    ),
                    const SizedBox(height: 16),

                    _buildDatePicker(),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),

            // Bottom Button
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 10,
                    offset: const Offset(0, -5),
                  ),
                ],
              ),
              child: SafeArea(
                top: false,
                child: Column(
                  children: [
                    if (!_canContinue())
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          children: [
                            Icon(Icons.warning_amber_rounded, 
                              color: Colors.orange.shade700, size: 16),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Please fill all required fields correctly',
                                style: GoogleFonts.poppins(
                                  fontSize: 11,
                                  color: Colors.orange.shade700,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _canContinue() ? _submit : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _canContinue() 
                            ? Colors.black 
                            : Colors.grey.shade300,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: _canContinue() ? 2 : 0,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              'Continue to ID Upload',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: _canContinue() ? Colors.white : Colors.grey.shade500,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Icon(
                              Icons.arrow_forward,
                              color: _canContinue() ? Colors.white : Colors.grey.shade500,
                              size: 20,
                            ),
                          ],
                        ),
                      ),
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

  // UI Helper Widgets
  Widget _buildSectionHeader({
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.black,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: Colors.white, size: 20),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(
                subtitle,
                style: GoogleFonts.poppins(
                  fontSize: 11,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildProgressStep(int step, String label, bool active, bool completed) {
    return Column(
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: active || completed ? Colors.black : Colors.grey.shade200,
            shape: BoxShape.circle,
          ),
          child: Center(
            child: completed
                ? const Icon(Icons.check, color: Colors.white, size: 18)
                : Text(
                    '$step',
                    style: GoogleFonts.poppins(
                      color: active || completed ? Colors.white : Colors.grey.shade400,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 10,
            fontWeight: active ? FontWeight.w600 : FontWeight.w400,
            color: active ? Colors.black : Colors.grey.shade500,
          ),
        ),
      ],
    );
  }

  Widget _buildProgressLine(bool active) => Container(
    height: 2,
    margin: const EdgeInsets.only(bottom: 28),
    color: active ? Colors.black : Colors.grey.shade300,
  );

  Widget _buildTextField(
    String label,
    TextEditingController controller,
    FocusNode focusNode, {
    required IconData icon,
    TextInputType? keyboardType,
    String? hint,
    Function(String)? onChanged,
    String? Function(String?)? validator,
  }) {
    final hasError = validator != null && 
                     controller.text.isNotEmpty && 
                     validator(controller.text) != null;
    
    return Column(
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
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: hasError 
                ? Colors.red.shade300 
                : focusNode.hasFocus 
                  ? Colors.black 
                  : Colors.grey.shade200,
              width: hasError || focusNode.hasFocus ? 2 : 1,
            ),
          ),
          child: TextField(
            controller: controller,
            focusNode: focusNode,
            keyboardType: keyboardType,
            onChanged: onChanged,
            style: GoogleFonts.poppins(fontSize: 14),
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: GoogleFonts.poppins(
                color: Colors.grey.shade400,
                fontSize: 14,
              ),
              prefixIcon: Icon(
                icon,
                color: focusNode.hasFocus ? Colors.black : Colors.grey.shade400,
                size: 20,
              ),
              suffixIcon: hasError
                ? Icon(Icons.error_outline, color: Colors.red.shade400, size: 20)
                : controller.text.isNotEmpty && validator != null && validator(controller.text) == null
                  ? Icon(Icons.check_circle, color: Colors.green.shade400, size: 20)
                  : null,
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            ),
          ),
        ),
        if (hasError)
          Padding(
            padding: const EdgeInsets.only(top: 6, left: 4),
            child: Text(
              validator(controller.text)!,
              style: GoogleFonts.poppins(
                fontSize: 11,
                color: Colors.red.shade600,
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildDropdown(
    String label,
    List<String> items,
    String? value,
    IconData icon,
    Function(String?) onChanged,
  ) {
    return Column(
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
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: value,
              isExpanded: true,
              hint: Text(
                'Select $label',
                style: GoogleFonts.poppins(
                  color: Colors.grey.shade400,
                  fontSize: 14,
                ),
              ),
              icon: Icon(Icons.keyboard_arrow_down, color: Colors.grey.shade600),
              items: items.map((item) => DropdownMenuItem(
                value: item,
                child: Row(
                  children: [
                    Icon(icon, size: 18, color: Colors.grey.shade600),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        item,
                        style: GoogleFonts.poppins(fontSize: 14),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              )).toList(),
              onChanged: onChanged,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDatePicker() {
    final hasValue = verification.dateOfBirth != null;
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Date of Birth',
          style: GoogleFonts.poppins(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: Colors.grey.shade700,
          ),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: () async {
            final date = await showDatePicker(
              context: context,
              initialDate: verification.dateOfBirth ?? DateTime(2000),
              firstDate: DateTime(1900),
              lastDate: DateTime.now(),
              builder: (context, child) {
                return Theme(
                  data: Theme.of(context).copyWith(
                    colorScheme: const ColorScheme.light(
                      primary: Colors.black,
                      onPrimary: Colors.white,
                      onSurface: Colors.black,
                    ),
                  ),
                  child: child!,
                );
              },
            );
            if (date != null) {
              setState(() => verification.dateOfBirth = date);
            }
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.calendar_today_outlined,
                  color: hasValue ? Colors.black : Colors.grey.shade400,
                  size: 20,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    hasValue
                        ? DateFormat('MMMM dd, yyyy').format(verification.dateOfBirth!)
                        : 'Select your date of birth',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: hasValue ? Colors.black : Colors.grey.shade400,
                    ),
                  ),
                ),
                if (hasValue)
                  Icon(Icons.check_circle, color: Colors.green.shade400, size: 20),
              ],
            ),
          ),
        ),
      ],
    );
  }
}