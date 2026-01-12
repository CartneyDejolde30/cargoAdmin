import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ReportScreen extends StatefulWidget {
  final String reportType; // 'car', 'user', 'booking', 'chat'
  final String reportedId; // ID of the item being reported
  final String reportedName; // Name of the item/person being reported
  
  const ReportScreen({
    super.key,
    required this.reportType,
    required this.reportedId,
    required this.reportedName,
  });

  @override
  State<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends State<ReportScreen> {
  String? selectedReason;
  final TextEditingController _detailsController = TextEditingController();
  bool isSubmitting = false;

  final String baseUrl = "http://10.139.150.2/carGOAdmin/";

  // Report reasons based on type
  Map<String, List<String>> reportReasons = {
    'car': [
      'Misleading information',
      'Fake photos',
      'Vehicle not as described',
      'Safety concerns',
      'Suspicious pricing',
      'Unavailable vehicle',
      'Other',
    ],
    'user': [
      'Inappropriate behavior',
      'Harassment',
      'Fraud/Scam',
      'Fake profile',
      'Suspicious activity',
      'Spam',
      'Other',
    ],
    'booking': [
      'No-show',
      'Late pickup/return',
      'Vehicle damage',
      'Cleanliness issues',
      'Payment dispute',
      'Cancellation issues',
      'Other',
    ],
    'chat': [
      'Harassment',
      'Spam messages',
      'Inappropriate content',
      'Scam attempt',
      'Threatening behavior',
      'Other',
    ],
  };

  @override
  void dispose() {
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _submitReport() async {
    if (selectedReason == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a reason')),
      );
      return;
    }

    if (_detailsController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please provide details')),
      );
      return;
    }

    setState(() => isSubmitting = true);

    try {
      final url = Uri.parse("${baseUrl}api/submit_report.php");
      final response = await http.post(url, body: {
        'report_type': widget.reportType,
        'reported_id': widget.reportedId,
        'reason': selectedReason,
        'details': _detailsController.text.trim(),
        // Add user_id from SharedPreferences in production
        'reporter_id': 'USER_ID_HERE',
      });

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        
        if (result['status'] == 'success') {
          if (mounted) {
            Navigator.pop(context);
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Report submitted successfully'),
                backgroundColor: Colors.green,
              ),
            );
          }
        } else {
          throw Exception(result['message'] ?? 'Failed to submit report');
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => isSubmitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final reasons = reportReasons[widget.reportType] ?? [];

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
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.arrow_back, color: Colors.black, size: 20),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Report ${_getTypeLabel()}',
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
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Info Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.orange.shade700),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Help us understand the issue. Your report will be reviewed by our team.',
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          color: Colors.orange.shade900,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Reporting
              Text(
                'You are reporting:',
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                widget.reportedName,
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),

              const SizedBox(height: 32),

              // Reason Selection
              Text(
                'Select a reason',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 16),

              ...reasons.map((reason) => _buildReasonOption(reason)).toList(),

              const SizedBox(height: 32),

              // Details
              Text(
                'Provide details',
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.black,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Please describe the issue in detail',
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 16),

              Container(
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: TextField(
                  controller: _detailsController,
                  maxLines: 6,
                  maxLength: 500,
                  decoration: InputDecoration(
                    hintText: 'Describe what happened...',
                    hintStyle: GoogleFonts.poppins(
                      color: Colors.grey.shade400,
                      fontSize: 14,
                    ),
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.all(16),
                  ),
                  style: GoogleFonts.poppins(fontSize: 14),
                ),
              ),

              const SizedBox(height: 32),

              // Submit Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: isSubmitting ? null : _submitReport,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
                  ),
                  child: isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : Text(
                          'Submit Report',
                          style: GoogleFonts.poppins(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                ),
              ),

              const SizedBox(height: 16),

              // Disclaimer
              Center(
                child: Text(
                  'False reports may result in account suspension',
                  style: GoogleFonts.poppins(
                    fontSize: 11,
                    color: Colors.grey.shade500,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildReasonOption(String reason) {
    final isSelected = selectedReason == reason;

    return GestureDetector(
      onTap: () => setState(() => selectedReason = reason),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? Colors.red.shade50 : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? Colors.red : Colors.grey.shade300,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: isSelected ? Colors.red : Colors.grey.shade400,
                  width: 2,
                ),
                color: isSelected ? Colors.red : Colors.transparent,
              ),
              child: isSelected
                  ? const Icon(Icons.check, size: 14, color: Colors.white)
                  : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                reason,
                style: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                  color: isSelected ? Colors.black : Colors.grey.shade700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _getTypeLabel() {
    switch (widget.reportType) {
      case 'car':
        return 'Car';
      case 'user':
        return 'User';
      case 'booking':
        return 'Booking';
      case 'chat':
        return 'Conversation';
      default:
        return 'Issue';
    }
  }
}