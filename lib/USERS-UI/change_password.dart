import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

const String baseUrl = "http://10.139.150.2/carGOAdmin";

class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final oldPassController = TextEditingController();
  final newPassController = TextEditingController();
  final confirmPassController = TextEditingController();
  
  bool loading = false;
  bool _obscureOld = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;

  // Password strength indicator
  double _passwordStrength = 0.0;
  String _strengthText = '';
  Color _strengthColor = Colors.grey;

  @override
  void initState() {
    super.initState();
    newPassController.addListener(_checkPasswordStrength);
  }

  @override
  void dispose() {
    oldPassController.dispose();
    newPassController.dispose();
    confirmPassController.dispose();
    super.dispose();
  }

  void _checkPasswordStrength() {
    final password = newPassController.text;
    double strength = 0.0;
    
    if (password.isEmpty) {
      setState(() {
        _passwordStrength = 0.0;
        _strengthText = '';
      });
      return;
    }

    // Length check
    if (password.length >= 8) strength += 0.25;
    if (password.length >= 12) strength += 0.25;

    // Contains uppercase
    if (password.contains(RegExp(r'[A-Z]'))) strength += 0.2;

    // Contains lowercase
    if (password.contains(RegExp(r'[a-z]'))) strength += 0.1;

    // Contains numbers
    if (password.contains(RegExp(r'[0-9]'))) strength += 0.1;

    // Contains special characters
    if (password.contains(RegExp(r'[!@#$%^&*(),.?":{}|<>]'))) strength += 0.1;

    String text = '';
    Color color = Colors.grey;

    if (strength <= 0.3) {
      text = 'Weak';
      color = Colors.red;
    } else if (strength <= 0.6) {
      text = 'Fair';
      color = Colors.orange;
    } else if (strength <= 0.8) {
      text = 'Good';
      color = Colors.blue;
    } else {
      text = 'Strong';
      color = Colors.green;
    }

    setState(() {
      _passwordStrength = strength;
      _strengthText = text;
      _strengthColor = color;
    });
  }

  Future<void> changePassword() async {
    // Validation
    if (oldPassController.text.trim().isEmpty) {
      _showMessage("Please enter your current password", isError: true);
      return;
    }

    if (newPassController.text.trim().isEmpty) {
      _showMessage("Please enter a new password", isError: true);
      return;
    }

    if (newPassController.text.length < 6) {
      _showMessage("New password must be at least 6 characters", isError: true);
      return;
    }

    if (newPassController.text != confirmPassController.text) {
      _showMessage("New passwords do not match", isError: true);
      return;
    }

    if (oldPassController.text == newPassController.text) {
      _showMessage("New password must be different from current password", isError: true);
      return;
    }

    SharedPreferences prefs = await SharedPreferences.getInstance();
    String userId = prefs.getString("user_id") ?? "";

    if (userId.isEmpty) {
      _showMessage("User not logged in", isError: true);
      return;
    }

    setState(() => loading = true);

    try {
      final response = await http.post(
        Uri.parse("$baseUrl/change_password.php"),
        body: {
          "user_id": userId,
          "old_password": oldPassController.text.trim(),
          "new_password": newPassController.text.trim(),
        },
      );

      setState(() => loading = false);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data["success"] == true) {
          _showMessage(data["message"] ?? "Password updated successfully", isError: false);
          
          // Clear fields
          oldPassController.clear();
          newPassController.clear();
          confirmPassController.clear();
          
          // Navigate back after delay
          await Future.delayed(const Duration(seconds: 1));
          if (mounted) Navigator.pop(context);
        } else {
          _showMessage(data["message"] ?? "Failed to update password", isError: true);
        }
      } else {
        _showMessage("Server error. Please try again", isError: true);
      }
    } catch (e) {
      setState(() => loading = false);
      _showMessage("Network error. Please check your connection", isError: true);
    }
  }

  void _showMessage(String msg, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              isError ? Icons.error_outline : Icons.check_circle_outline,
              color: Colors.white,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                msg,
                style: GoogleFonts.poppins(fontSize: 14),
              ),
            ),
          ],
        ),
        backgroundColor: isError ? Colors.red.shade600 : Colors.green.shade600,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        duration: Duration(seconds: isError ? 3 : 2),
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
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.black,
              size: 16,
            ),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          "Change Password",
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Security icon and description
              Center(
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.lock_outline,
                    size: 48,
                    color: Colors.grey.shade700,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              
              Text(
                'Secure Your Account',
                style: GoogleFonts.poppins(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              
              Text(
                'Create a strong password to keep your account safe and secure.',
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),

              // Current Password
              _buildPasswordField(
                label: "Current Password",
                controller: oldPassController,
                obscureText: _obscureOld,
                onToggleVisibility: () => setState(() => _obscureOld = !_obscureOld),
                prefixIcon: Icons.lock_clock,
              ),
              const SizedBox(height: 20),

              // New Password
              _buildPasswordField(
                label: "New Password",
                controller: newPassController,
                obscureText: _obscureNew,
                onToggleVisibility: () => setState(() => _obscureNew = !_obscureNew),
                prefixIcon: Icons.lock_open,
              ),
              
              // Password strength indicator
              if (newPassController.text.isNotEmpty) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: LinearProgressIndicator(
                          value: _passwordStrength,
                          backgroundColor: Colors.grey.shade200,
                          valueColor: AlwaysStoppedAnimation<Color>(_strengthColor),
                          minHeight: 6,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      _strengthText,
                      style: GoogleFonts.poppins(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: _strengthColor,
                      ),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 20),

              // Confirm Password
              _buildPasswordField(
                label: "Confirm New Password",
                controller: confirmPassController,
                obscureText: _obscureConfirm,
                onToggleVisibility: () => setState(() => _obscureConfirm = !_obscureConfirm),
                prefixIcon: Icons.lock_reset,
              ),
              const SizedBox(height: 32),

              // Password requirements
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
                        Icon(Icons.info_outline, size: 18, color: Colors.blue.shade700),
                        const SizedBox(width: 8),
                        Text(
                          'Password Requirements',
                          style: GoogleFonts.poppins(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Colors.blue.shade900,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _buildRequirement('At least 8 characters long'),
                    _buildRequirement('Mix of uppercase & lowercase letters'),
                    _buildRequirement('At least one number'),
                    _buildRequirement('At least one special character'),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              // Update Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: loading ? null : changePassword,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    disabledBackgroundColor: Colors.grey.shade300,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
                  ),
                  child: loading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : Text(
                          "Update Password",
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPasswordField({
    required String label,
    required TextEditingController controller,
    required bool obscureText,
    required VoidCallback onToggleVisibility,
    required IconData prefixIcon,
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
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          obscureText: obscureText,
          style: GoogleFonts.poppins(fontSize: 15),
          decoration: InputDecoration(
            prefixIcon: Icon(prefixIcon, color: Colors.grey.shade600, size: 20),
            suffixIcon: IconButton(
              icon: Icon(
                obscureText ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                color: Colors.grey.shade600,
                size: 20,
              ),
              onPressed: onToggleVisibility,
            ),
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
              borderSide: const BorderSide(color: Colors.black, width: 1.5),
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          ),
        ),
      ],
    );
  }

  Widget _buildRequirement(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Icon(
            Icons.check_circle_outline,
            size: 16,
            color: Colors.blue.shade700,
          ),
          const SizedBox(width: 8),
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