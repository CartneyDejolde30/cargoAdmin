import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../../Owner/verification/personal_info_screen.dart';

class VerifyPopup {
  /// Main method to show verification popup
  /// Set showForRentersOnly to true to only show on renters.dart
  static Future<void> showIfNotVerified(
    BuildContext context, {
    bool showForRentersOnly = false,
  }) async {
    // Skip if this should only show for renters and we're not calling from renters
    if (!showForRentersOnly) {
      print("⏭️ Verification popup disabled for this screen");
      return;
    }

    // Get user ID from SharedPreferences
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('user_id');

    if (userId == null || userId.isEmpty) {
      print("❌ No user ID found - skipping verification popup");
      return; // Don't show popup if not logged in
    }

    // Check verification status from database
    final isVerified = await _checkVerificationFromDatabase(userId);
    
    print("🔍 Verification check result: $isVerified");

    if (isVerified) {
      print("✅ User is verified - popup will NOT show");
      return; // User is verified, don't show popup
    }

    print("⚠️ User is NOT verified - showing popup");

    // Show popup if not verified
    await showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        backgroundColor: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                /// Close Button
                Align(
                  alignment: Alignment.topLeft,
                  child: IconButton(
                    icon: const Icon(Icons.close, color: Colors.grey),
                    padding: EdgeInsets.zero,
                    onPressed: () => Navigator.pop(dialogContext),
                  ),
                ),

                const SizedBox(height: 20),

                /// Illustration / Icon Section
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Align(
                      alignment: Alignment.centerRight,
                      child: CircleAvatar(
                        radius: 75,
                        backgroundColor: Colors.green[300],
                        child: const Icon(Icons.emoji_emotions, size: 80, color: Colors.white),
                      ),
                    ),
                    Positioned(
                      top: 0,
                      right: 20,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.black,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          'Just takes 2 mins!',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.greenAccent,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 30),

                /// Title Text
                Text(
                  'Hi there!\nLet\'s get you\nverified first.',
                  style: GoogleFonts.poppins(
                    fontSize: 28,
                    height: 1.2,
                    fontWeight: FontWeight.bold,
                  ),
                ),

                const SizedBox(height: 12),

                /// Subtitle
                Text(
                  'Get verified so you can book freely anytime, anywhere with Cargo.',
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    height: 1.4,
                    color: Colors.grey[700],
                  ),
                ),

                const SizedBox(height: 24),

                /// Steps
                _stepText('1. Prepare your Driver\'s License or any Government ID.'),
                _stepText('2. Take a selfie holding your ID.'),
                _stepText('3. Fill out the Verification Information Sheet.'),
                _stepText('4. Wait for Cargo Approval.'),

                const SizedBox(height: 30),

                /// Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      backgroundColor: Colors.black,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () {
                      Navigator.pop(dialogContext); // Close popup
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const PersonalInfoScreen(),
                        ),
                      );
                    },
                    child: Text(
                      'Get Verified',
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        color: Colors.greenAccent,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                )
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Check verification status from database
  static Future<bool> _checkVerificationFromDatabase(String userId) async {
    const String baseUrl = "http://10.139.150.2/carGOAdmin/";
    
    try {
      final url = Uri.parse("${baseUrl}api/check_verification.php?user_id=$userId");
      print("📡 Checking verification: $url");
      
      final response = await http.get(url).timeout(const Duration(seconds: 10));
      
      print("📥 Response status: ${response.statusCode}");
      print("📥 Response body: ${response.body}");

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        return result['is_verified'] == true;
      }
      
      return false;
    } catch (e) {
      print("❌ Error checking verification: $e");
      return false; // On error, assume not verified (safer)
    }
  }

  /// Reusable step text widget
  static Widget _stepText(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Text(
        text,
        style: GoogleFonts.poppins(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: Colors.black87,
        ),
      ),
    );
  }
} 