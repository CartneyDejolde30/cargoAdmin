import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_photos_diagram_screen.dart';

class UploadDocumentsScreen extends StatefulWidget {
  final CarListing listing;
  final String vehicleType;

  const UploadDocumentsScreen({
    super.key,
    required this.listing,
    this.vehicleType = 'car',
  });

  @override
  State<UploadDocumentsScreen> createState() => _UploadDocumentsScreenState();
}

class _UploadDocumentsScreenState extends State<UploadDocumentsScreen> {
  // CHANGED: Store File objects instead of just paths
  File? officialReceiptFile;
  File? certificateOfRegistrationFile;

  @override
  void initState() {
    super.initState();
    // Try to restore from paths if coming back (mobile only)
    if (!kIsWeb) {
      if (widget.listing.officialReceipt != null) {
        officialReceiptFile = File(widget.listing.officialReceipt!);
      }
      if (widget.listing.certificateOfRegistration != null) {
        certificateOfRegistrationFile = File(widget.listing.certificateOfRegistration!);
      }
    }
  }

  bool _canContinue() {
    return officialReceiptFile != null && certificateOfRegistrationFile != null;
  }

  Future<void> _pickDocument(bool isOR) async {
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(source: ImageSource.gallery);

    if (image != null) {
      // FIXED: Properly convert XFile to File for both platforms
      File imageFile;
      if (kIsWeb) {
        // Web: Create File from bytes
        final bytes = await image.readAsBytes();
        imageFile = File.fromRawPath(bytes);
      } else {
        // Mobile: Use path
        imageFile = File(image.path);
      }

      setState(() {
        if (isOR) {
          officialReceiptFile = imageFile;
          widget.listing.officialReceipt = image.path; // Store path for reference
        } else {
          certificateOfRegistrationFile = imageFile;
          widget.listing.certificateOfRegistration = image.path;
        }
      });
    }
  }

  Widget _buildUploadBox(String label, File? file, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 160,
        decoration: BoxDecoration(
          color: Colors.grey[50],
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white, width: 2),
        ),
        child: file == null
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.3),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.image,
                      size: 32,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Upload',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.black,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              )
            : Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: kIsWeb
                        ? Image.network(
                            file.path, // On web, File.path is a blob URL
                            width: double.infinity,
                            height: double.infinity,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return Container(
                                width: double.infinity,
                                height: double.infinity,
                                color: Colors.grey[300],
                                child: const Icon(
                                  Icons.broken_image,
                                  size: 50,
                                  color: Colors.grey,
                                ),
                              );
                            },
                          )
                        : Image.file(
                            file,
                            width: double.infinity,
                            height: double.infinity,
                            fit: BoxFit.cover,
                          ),
                  ),
                  Positioned(
                    top: 8,
                    right: 8,
                    child: IconButton(
                      icon: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.close, size: 18),
                      ),
                      onPressed: () {
                        setState(() {
                          if (label == 'Official Receipt') {
                            officialReceiptFile = null;
                            widget.listing.officialReceipt = null;
                          } else {
                            certificateOfRegistrationFile = null;
                            widget.listing.certificateOfRegistration = null;
                          }
                        });
                      },
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
                      'Upload OR/CR',
                      style: GoogleFonts.poppins(
                        fontSize: 24,
                        fontWeight: FontWeight.w600,
                        color: Colors.black,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Upload clear copy of Official Receipt and Certificate of Registration',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        color: Colors.black87,
                      ),
                    ),
                    const SizedBox(height: 32),
                    Text(
                      'Official Receipt',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildUploadBox(
                      'Official Receipt',
                      officialReceiptFile,
                      () => _pickDocument(true),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Certificate of Registration',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildUploadBox(
                      'Certificate of Registration',
                      certificateOfRegistrationFile,
                      () => _pickDocument(false),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(24),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _canContinue()
                      ? () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => CarPhotosDiagramScreen(
                                listing: widget.listing,
                                vehicleType: widget.vehicleType,
                              ),
                            ),
                          );
                        }
                      : null,
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
                      color: _canContinue() ? Colors.white : Colors.grey[500],
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