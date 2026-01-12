import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/submit_car_api.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

class CarPhotosDiagramScreen extends StatefulWidget {
  final CarListing listing;
  final String vehicleType;

  const CarPhotosDiagramScreen({
    super.key,
    required this.listing,
    this.vehicleType = 'car',
  });

  @override
  State<CarPhotosDiagramScreen> createState() => _CarPhotosDiagramScreenState();
}

class _CarPhotosDiagramScreenState extends State<CarPhotosDiagramScreen> {
  List<File> capturedPhotos = [];
  File? mainCarPhoto;

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
        title: Text(
          isMoto ? "Take Motorcycle Photos" : "Take Car Photos",
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.w600,
            color: Colors.black,
          ),
        ),
        centerTitle: true,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  Text(
                    "Capture clear photos of your vehicle",
                    style: GoogleFonts.poppins(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildUploadTile(
                    isMoto ? "Upload Main Motorcycle Photo" : "Upload Main Car Photo", 
                    isMain: true
                  ),
                  const SizedBox(height: 20),
                  Text(
                    "Additional Required Photos:",
                    style: GoogleFonts.poppins(fontWeight: FontWeight.w500),
                  ),
                  const SizedBox(height: 12),
                  ...List.generate(
                    5,
                    (i) => _buildUploadTile("Photo Spot ${i + 1}", index: i),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(24),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _canSubmit() ? _submitListing : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(30),
                    ),
                  ),
                  child: Text(
                    "Finish & Publish",
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: _canSubmit() ? Colors.white : Colors.grey[500],
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

  bool _canSubmit() {
    return mainCarPhoto != null;
  }

  Widget _buildUploadTile(String label, {bool isMain = false, int? index}) {
    File? imageFile;

    if (isMain && mainCarPhoto != null) {
      imageFile = mainCarPhoto;
    } else if (index != null && index < capturedPhotos.length) {
      imageFile = capturedPhotos[index];
    }

    return GestureDetector(
      onTap: () => _pickPhoto(isMain, index),
      child: Container(
        height: 150,
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.grey[50],
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.black26),
        ),
        child: imageFile == null
            ? Center(
                child: Text(
                  label,
                  style: GoogleFonts.poppins(color: Colors.black54),
                ),
              )
            : ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: kIsWeb
                    ? Image.network(
                        imageFile.path,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) {
                          return Container(
                            color: Colors.grey[300],
                            child: const Icon(
                              Icons.broken_image,
                              size: 50,
                              color: Colors.grey,
                            ),
                          );
                        },
                      )
                    : Image.file(imageFile, fit: BoxFit.cover),
              ),
      ),
    );
  }

  Future<void> _pickPhoto(bool isMain, int? index) async {
    final picker = ImagePicker();
    final XFile? img = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
    );

    if (img == null) return;

    File imageFile;
    if (kIsWeb) {
      final bytes = await img.readAsBytes();
      imageFile = File.fromRawPath(bytes);
    } else {
      imageFile = File(img.path);
    }

    setState(() {
      if (isMain) {
        mainCarPhoto = imageFile;
      } else {
        if (index! < capturedPhotos.length) {
          capturedPhotos[index] = imageFile;
        } else {
          capturedPhotos.add(imageFile);
        }
      }
    });
  }

  Future<void> _submitListing() async {
    final isMoto = widget.vehicleType == 'motorcycle';
    
    // Show loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    // Prepare document files
    File? orFile;
    File? crFile;

    if (widget.listing.officialReceipt != null) {
      if (kIsWeb) {
        print("⚠️ Web: OR file handling needs update");
      } else {
        orFile = File(widget.listing.officialReceipt!);
      }
    }

    if (widget.listing.certificateOfRegistration != null) {
      if (kIsWeb) {
        print("⚠️ Web: CR file handling needs update");
      } else {
        crFile = File(widget.listing.certificateOfRegistration!);
      }
    }

    // 🔧 FIX: Use submitVehicleListing with vehicleType parameter
    final success = await submitVehicleListing(
      listing: widget.listing,
      mainPhoto: mainCarPhoto,
      orFile: orFile,
      crFile: crFile,
      extraPhotos: capturedPhotos,
      vehicleType: widget.vehicleType, // ✅ CRITICAL: Pass vehicle type
    );

    if (!mounted) return;
    Navigator.pop(context); // Close loading dialog

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isMoto
                ? "Motorcycle uploaded successfully ✔"
                : "Car uploaded successfully ✔"
          ),
        ),
      );

      await Future.delayed(const Duration(milliseconds: 300));

      if (!mounted) return;

      // Navigate to MyCars screen
      Navigator.pushNamedAndRemoveUntil(
        context,
        '/mycars',
        (route) => false,
        arguments: widget.listing.owner,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Failed to upload. Try again.")),
      );
    }
  }
}