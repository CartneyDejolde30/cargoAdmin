import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:convert';
import 'package:flutter_application_1/USERS-UI/Owner/models/user_verification.dart';
import 'package:flutter_application_1/USERS-UI/Owner/services/verification_service.dart';

class SelfieScreen extends StatefulWidget {
  final UserVerification verification;

  const SelfieScreen({Key? key, required this.verification}) : super(key: key);

  @override
  State<SelfieScreen> createState() => _SelfieScreenState();
}

class _SelfieScreenState extends State<SelfieScreen> {
  final ImagePicker _picker = ImagePicker();
  File? _selfieImage;
  Uint8List? _webImageBytes;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    
    // Only load existing selfie if it exists (for mobile)
    if (!kIsWeb && widget.verification.selfiePhoto != null) {
      _selfieImage = File(widget.verification.selfiePhoto!);
    }
  }

  // ❌ REMOVE THE ENTIRE _loadWebImages() METHOD
  // Delete lines 39-60 from your original code

  Future<void> _takeSelfie() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.camera,
        preferredCameraDevice: CameraDevice.front,
        imageQuality: 85,
      );

      if (image != null) {
  final bytes = await image.readAsBytes();
  setState(() {
    if (kIsWeb) {
      _webImageBytes = bytes;
    } else {
      _selfieImage = File(image.path);
    }
    widget.verification.selfiePhoto = base64Encode(bytes);
  });
}

    } catch (_) {
      _showError("Camera error. Please try again");
    }
  }

  // ... rest of your code stays the same

  Future<void> _pickFromGallery() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 85,
      );

      if (image != null) {
  final bytes = await image.readAsBytes();
  setState(() {
    if (kIsWeb) {
      _webImageBytes = bytes;
      widget.verification.selfiePhoto = base64Encode(bytes);
    } else {
      _selfieImage = File(image.path);
      widget.verification.selfieFile = File(image.path);
    }
  });
}

    } catch (_) {
      _showError("Failed to access gallery");
    }
  }

  void _showImageSourceOptions() {
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
              title: 'Take Selfie',
              subtitle: 'Use front camera',
              onTap: () {
                Navigator.pop(context);
                _takeSelfie();
              },
            ),
            const SizedBox(height: 12),

            _buildBottomSheetOption(
              icon: Icons.photo_library,
              title: 'Choose from Gallery',
              subtitle: 'Select existing photo',
              onTap: () {
                Navigator.pop(context);
                _pickFromGallery();
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

  bool _hasImage() => kIsWeb ? _webImageBytes != null : _selfieImage != null;

Future<void> _submitVerification() async {
  print("🟦 DEBUG USER ID: ${widget.verification.userId}");


  if (!_hasImage()) {
    _showError("Please upload a selfie first");
    return;
  }

  setState(() => _isSubmitting = true);

  try {
    print("🚀 Starting verification submission...");
    print("📝 User ID: ${widget.verification.userId}");
    
    final result = await VerificationService.submitVerification(widget.verification);

    print("📦 Submission Result: $result");

    if (result['success'] == true) {
      print("✅ SUCCESS! Verification ID: ${result['verification_id']}");
      
      if (mounted) {
        _showSuccessDialog();
      }
    } else {
      print("❌ FAILED: ${result['message']}");
      
      if (mounted) {
        // Show user-friendly error message
        String errorMessage = result['message'] ?? "Submission failed. Please try again.";
        
        // Handle specific error cases
        if (errorMessage.contains("already submitted")) {
          errorMessage = "You have already submitted a verification. Please wait for admin approval.";
        } else if (errorMessage.contains("already verified")) {
          errorMessage = "Your account is already verified!";
        }
        
        _showError(errorMessage);
      }
    }
  } catch (e) {
    print("❌ Unexpected Error: $e");
    
    if (mounted) {
      _showError("An unexpected error occurred. Please check your connection and try again.");
    }
  } finally {
    if (mounted) {
      setState(() => _isSubmitting = false);
    }
  }
}
  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: const EdgeInsets.all(32),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                shape: BoxShape.circle,
              ),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.green.shade100,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.check_rounded,
                  size: 48,
                  color: Colors.green.shade700,
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Verification Submitted!',
              style: GoogleFonts.poppins(
                fontSize: 22,
                fontWeight: FontWeight.w700,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              'Your verification is under review.',
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade700,
                fontWeight: FontWeight.w500,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'You will receive an update within 24-48 hours.',
              style: GoogleFonts.poppins(
                fontSize: 13,
                color: Colors.grey.shade600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.of(context).popUntil((route) => route.isFirst);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                child: Text(
                  'Done',
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
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

  @override
  Widget build(BuildContext context) {
    final canSubmit = _hasImage() && !_isSubmitting;

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
                      _buildProgressStep(2, "ID Upload", true, true),
                      Expanded(child: _buildProgressLine(true)),
                      _buildProgressStep(3, "Selfie", true, false),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.purple.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.purple.shade100),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.face_retouching_natural, 
                          color: Colors.purple.shade700, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'Final step: Take a clear selfie with your ID',
                            style: GoogleFonts.poppins(
                              fontSize: 12,
                              color: Colors.purple.shade900,
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
                      icon: Icons.photo_camera_front,
                      title: 'Selfie with ID',
                      subtitle: 'Hold your ID next to your face',
                    ),
                    const SizedBox(height: 24),

                    _selfiePreview(),
                    const SizedBox(height: 24),

                    // Guidelines Section
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue.shade100),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.checklist_rounded, 
                                color: Colors.blue.shade700, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'Photo Guidelines',
                                style: GoogleFonts.poppins(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.blue.shade900,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          _buildGuideline('Hold ID next to your face', Icons.badge),
                          _buildGuideline('Ensure good lighting', Icons.wb_sunny),
                          _buildGuideline('Face camera directly', Icons.face),
                          _buildGuideline('ID text must be readable', Icons.text_fields),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Warning Notice
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.amber.shade200),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.warning_amber_rounded, 
                            color: Colors.amber.shade700, size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Make sure your face and ID details are clearly visible in the photo',
                              style: GoogleFonts.poppins(
                                fontSize: 12,
                                color: Colors.amber.shade900,
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
                    if (!canSubmit && !_isSubmitting)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          children: [
                            Icon(Icons.warning_amber_rounded,
                              color: Colors.orange.shade700, size: 16),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Please take a selfie to continue',
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
                        onPressed: canSubmit ? _submitVerification : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: canSubmit
                              ? Colors.black
                              : Colors.grey.shade300,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: canSubmit ? 2 : 0,
                        ),
                        child: _isSubmitting
                            ? const SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.5,
                                  valueColor: AlwaysStoppedAnimation(Colors.white),
                                ),
                              )
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(
                                    'Submit Verification',
                                    style: GoogleFonts.poppins(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w600,
                                      color: canSubmit
                                          ? Colors.white
                                          : Colors.grey.shade500,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Icon(
                                    Icons.check_circle_outline,
                                    color: canSubmit
                                        ? Colors.white
                                        : Colors.grey.shade500,
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
                      color: active || completed
                          ? Colors.white
                          : Colors.grey.shade400,
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

  Widget _selfiePreview() {
    final hasImage = _hasImage();

    return GestureDetector(
      onTap: _showImageSourceOptions,
      child: Container(
        height: 400,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          color: Colors.white,
          border: Border.all(
            width: hasImage ? 2 : 1,
            color: hasImage ? Colors.green.shade400 : Colors.grey.shade200,
          ),
        ),
        child: hasImage
            ? Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: _imageWidget(),
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
                            'Tap to retake',
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
            : _placeholder(),
      ),
    );
  }

  Widget _imageWidget() {
    if (kIsWeb && _webImageBytes != null) {
      return Image.memory(
        _webImageBytes!,
        width: double.infinity,
        height: double.infinity,
        fit: BoxFit.cover,
      );
    }
    if (!kIsWeb && _selfieImage != null) {
      return Image.file(
        _selfieImage!,
        width: double.infinity,
        height: double.infinity,
        fit: BoxFit.cover,
      );
    }
    return const SizedBox();
  }

  Widget _placeholder() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            shape: BoxShape.circle,
          ),
          child: Icon(
            Icons.photo_camera_front,
            size: 60,
            color: Colors.grey.shade600,
          ),
        ),
        const SizedBox(height: 24),
        Text(
          'Tap to take selfie',
          style: GoogleFonts.poppins(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: Colors.grey.shade700,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Hold your ID next to your face',
          style: GoogleFonts.poppins(
            fontSize: 13,
            color: Colors.grey.shade500,
          ),
        ),
      ],
    );
  }

  Widget _buildGuideline(String text, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: Colors.blue.shade700),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 12,
                color: Colors.blue.shade900,
              ),
            ),
          ),
        ],
      ),
    );
  }
}