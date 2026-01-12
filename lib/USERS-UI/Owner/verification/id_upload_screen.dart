import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'package:flutter_application_1/USERS-UI/Owner/models/user_verification.dart';
import 'package:flutter_application_1/USERS-UI/Owner/verification/selfie_screen.dart';

class IDUploadScreen extends StatefulWidget {
  final UserVerification verification;

  const IDUploadScreen({Key? key, required this.verification}) : super(key: key);

  @override
  State<IDUploadScreen> createState() => _IDUploadScreenState();
}

class _IDUploadScreenState extends State<IDUploadScreen> {
  final ImagePicker _picker = ImagePicker();
  File? _frontImage;
  File? _backImage;

  String? _selectedIdType;

  final List<Map<String, String>> idTypes = [
    {'value': 'drivers_license', 'label': 'Driver\'s License'},
    {'value': 'passport', 'label': 'Passport'},
    {'value': 'national_id', 'label': 'National ID'},
    {'value': 'umid', 'label': 'UMID'},
    {'value': 'sss', 'label': 'SSS ID'},
    {'value': 'philhealth', 'label': 'PhilHealth ID'},
    {'value': 'voters_id', 'label': 'Voter\'s ID'},
    {'value': 'postal_id', 'label': 'Postal ID'},
  ];

  @override
  void initState() {
    super.initState();

    // Restore previously entered data if user navigates back
    _selectedIdType = widget.verification.idType;
    
    if (widget.verification.idFrontPhoto != null) {
  if (!kIsWeb) {
    _frontImage = File(widget.verification.idFrontPhoto!);
  }
}
if (widget.verification.idBackPhoto != null) {
  if (!kIsWeb) {
    _backImage = File(widget.verification.idBackPhoto!);
  }
}

  }

  // Camera Take
  Future<void> _pickImage(bool isFront) async {
  try {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
    );

    if (image != null) {
      setState(() {
        if (isFront) {
          _frontImage = File(image.path);
          widget.verification.idFrontFile = _frontImage;
          widget.verification.idFrontPhoto = image.path;

        } else {
          _backImage = File(image.path);
          widget.verification.idBackFile = _backImage;
          widget.verification.idBackPhoto = image.path;

        }
      });
    }
  } catch (e) {
    _showError("Failed to capture image");
  }
}


  // Pick from Gallery
  Future<void> _pickFromGallery(bool isFront) async {
  try {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );

    if (image != null) {
      setState(() {
        if (isFront) {
          _frontImage = File(image.path);
          widget.verification.idFrontFile = _frontImage;
        } else {
          _backImage = File(image.path);
          widget.verification.idBackFile = _backImage;
        }
      });
    }
  } catch (e) {
    _showError("Failed to pick from gallery");
  }
}


  // Bottom sheet selector
  void _showImageSourceOptions(bool isFront) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.only(bottom: 20),
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Text(
              'Choose Image Source',
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 24),

            _buildBottomSheetOption(
              icon: Icons.camera_alt,
              title: 'Camera',
              subtitle: 'Take a photo now',
              onTap: () {
                Navigator.pop(context);
                _pickImage(isFront);
              },
            ),
            const SizedBox(height: 12),

            _buildBottomSheetOption(
              icon: Icons.photo_library,
              title: 'Gallery',
              subtitle: 'Choose from your photos',
              onTap: () {
                Navigator.pop(context);
                _pickFromGallery(isFront);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomSheetOption({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.grey.shade50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.black,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: Colors.white, size: 24),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.poppins(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey.shade400),
          ],
        ),
      ),
    );
  }

  void _showError(String text) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.error_outline, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(text)),
          ],
        ),
        backgroundColor: Colors.red.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _continue() {
    if (_selectedIdType == null) {
  _showError("Please select an ID type");
  return;
}


    if (_frontImage == null || _backImage == null) {
  _showError("Please upload front and back images of the ID");
  return;
}

    // Save selection to model
    widget.verification.idType = _selectedIdType;

    Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => SelfieScreen(verification: widget.verification)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final canContinue =
    _selectedIdType != null &&
    _frontImage != null &&
    _backImage != null;

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
                      _buildProgressStep(1, "Personal", true, true),
                      Expanded(child: _buildProgressLine(true)),
                      _buildProgressStep(2, "ID Upload", true, false),
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
                        Icon(Icons.verified_user, color: Colors.blue.shade700, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'Your ID will be securely stored and encrypted',
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
                      icon: Icons.badge_outlined,
                      title: 'Upload Valid ID',
                      subtitle: 'Government-issued identification required',
                    ),
                    const SizedBox(height: 24),

                    // Dropdown for ID selection
                    _buildDropdown(),
                    const SizedBox(height: 32),

                    // Upload sections
                    _buildUploadSection('Front of ID', true),
                    const SizedBox(height: 24),

                    _buildUploadSection('Back of ID', false),
                    const SizedBox(height: 24),

                    // Tips Section
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.amber.shade200),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.lightbulb_outline, 
                                color: Colors.amber.shade700, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'Photo Tips',
                                style: GoogleFonts.poppins(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.amber.shade900,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          _buildTip('Ensure all text is clearly visible'),
                          _buildTip('Take photo in good lighting'),
                          _buildTip('Avoid glare and shadows'),
                          _buildTip('Keep ID flat and fully visible'),
                        ],
                      ),
                    ),
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
                    if (!canContinue)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          children: [
                            Icon(Icons.warning_amber_rounded, 
                              color: Colors.orange.shade700, size: 16),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Please select ID type and upload both sides',
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
                        onPressed: canContinue ? _continue : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: canContinue 
                            ? Colors.black 
                            : Colors.grey.shade300,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: canContinue ? 2 : 0,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              'Continue to Selfie',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: canContinue ? Colors.white : Colors.grey.shade500,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Icon(
                              Icons.arrow_forward,
                              color: canContinue ? Colors.white : Colors.grey.shade500,
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

  Widget _buildDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'ID Type',
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
              value: _selectedIdType,
              isExpanded: true,
              hint: Text(
                'Select ID Type',
                style: GoogleFonts.poppins(
                  color: Colors.grey.shade400,
                  fontSize: 14,
                ),
              ),
              icon: Icon(Icons.keyboard_arrow_down, color: Colors.grey.shade600),
              items: idTypes.map((id) => DropdownMenuItem(
                value: id['value'],
                child: Row(
                  children: [
                    Icon(Icons.credit_card, size: 18, color: Colors.grey.shade600),
                    const SizedBox(width: 12),
                    Text(
                      id['label']!,
                      style: GoogleFonts.poppins(fontSize: 14),
                    ),
                  ],
                ),
              )).toList(),
              onChanged: (value) {
                setState(() {
                  _selectedIdType = value;
                  widget.verification.idType = value;
                });
              },
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildUploadSection(String title, bool isFront) {
    final image = isFront ? _frontImage : _backImage;
    final hasImage = image != null;


    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: GoogleFonts.poppins(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: Colors.grey.shade700,
          ),
        ),
        const SizedBox(height: 8),

        GestureDetector(
          onTap: () => _showImageSourceOptions(isFront),
          child: Container(
            height: 200,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: hasImage ? Colors.green.shade400 : Colors.grey.shade200,
                width: hasImage ? 2 : 1,
              ),
            ),
            child: hasImage
                ? Stack(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: Image.file(
                          image,
                          width: double.infinity,
                          height: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),

                      Positioned(
                        top: 8,
                        right: 8,
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.green.shade400,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.check,
                            color: Colors.white,
                            size: 20,
                          ),
                        ),
                      ),
                      Positioned(
                        bottom: 12,
                        left: 12,
                        right: 12,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.7),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(
                                Icons.edit,
                                color: Colors.white,
                                size: 16,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                'Tap to change',
                                style: GoogleFonts.poppins(
                                  fontSize: 12,
                                  color: Colors.white,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          Icons.add_photo_alternate_outlined,
                          size: 40,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Tap to upload $title',
                        style: GoogleFonts.poppins(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey.shade700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Camera or Gallery',
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildTip(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 4,
            height: 4,
            margin: const EdgeInsets.only(top: 7, right: 8),
            decoration: BoxDecoration(
              color: Colors.amber.shade700,
              shape: BoxShape.circle,
            ),
          ),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 12,
                color: Colors.amber.shade900,
              ),
            ),
          ),
        ],
      ),
    );
  }
}