import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'req_model/pending_request_card.dart';
import 'req_model/request_dialog.dart';
import 'req_model/booking_request.dart';
import 'req_model/request_details_page.dart';

class PendingRequestsPage extends StatelessWidget {
  final String ownerId;

  const PendingRequestsPage({super.key, required this.ownerId});

  /* ================= FETCH REAL DATA ================= */
  Future<List<BookingRequest>> fetchPendingRequests() async {
    final url = Uri.parse(
      "http://10.139.150.2/carGOAdmin/api/get_pending_requests.php?owner_id=$ownerId",
    );

    try {
      final response = await http.get(url).timeout(
        const Duration(seconds: 10),
      );

      if (response.statusCode != 200 || response.body.isEmpty) {
        return [];
      }

      final data = jsonDecode(response.body);

      if (data["success"] == true && data["requests"] is List) {
        return (data["requests"] as List)
            .map((e) => BookingRequest.fromJson(e))
            .toList();
      }

      return [];
    } catch (e) {
      debugPrint("❌ Fetch pending requests error: $e");
      return [];
    }
  }

  /* ================= UI ================= */
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: CustomScrollView(
        slivers: [
          /* ================= APP BAR ================= */
          SliverAppBar(
            expandedHeight: 120,
            pinned: true,
            backgroundColor: Colors.white,
            elevation: 0,
            leading: IconButton(
              icon: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.black,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.arrow_back_ios_new,
                  color: Colors.white,
                  size: 18,
                ),
              ),
              onPressed: () => Navigator.pop(context),
            ),
            flexibleSpace: FlexibleSpaceBar(
              centerTitle: true,
              title: Text(
                'Pending Requests',
                style: GoogleFonts.outfit(
                  color: Colors.black,
                  fontWeight: FontWeight.w700,
                  fontSize: 22,
                ),
              ),
            ),
            actions: [
              Container(
                margin: const EdgeInsets.only(right: 16),
                decoration: BoxDecoration(
                  color: Colors.black,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: IconButton(
                  icon: const Icon(Icons.tune, color: Colors.white),
                  onPressed: () {},
                ),
              ),
            ],
          ),

          /* ================= LIST ================= */
          SliverToBoxAdapter(
            child: FutureBuilder<List<BookingRequest>>(
              future: fetchPendingRequests(),
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return SizedBox(
                    height: 400,
                    child: const Center(
                      child: CircularProgressIndicator(
                        color: Colors.black,
                        strokeWidth: 2,
                      ),
                    ),
                  );
                }

                if (!snapshot.hasData || snapshot.data!.isEmpty) {
                  return SizedBox(
                    height: 400,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.inbox_outlined,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          "No pending requests",
                          style: GoogleFonts.outfit(
                            fontSize: 18,
                            color: Colors.grey[600],
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  );
                }

                final requests = snapshot.data!;

                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    children: [
                      const SizedBox(height: 8),
                      ...requests.map(
                        (request) => PendingRequestCard(
                          request: request,
                          ownerId: ownerId,
                          onApprove: () =>
                              _handleApprove(request, context),
                          onReject: () =>
                              _handleReject(request, context),
                          onTap: () =>
                              _navigateToDetails(request, context),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  /* ================= ACTIONS ================= */

  void _navigateToDetails(
    BookingRequest request,
    BuildContext context,
  ) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => RequestDetailsPage(
          request: request,
          ownerId: ownerId,
        ),
      ),
    );
  }

  void _handleReject(
    BookingRequest request,
    BuildContext context,
  ) {
    RequestDialogs.showRejectDialog(
      context,
      request.bookingId,
      ownerId,
    );
  }

  void _handleApprove(
    BookingRequest request,
    BuildContext context,
  ) async {
    final url = Uri.parse(
      "http://10.139.150.2/carGOAdmin/api/approve_request.php",
    );

    try {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => const Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
      );

      final response = await http.post(
        url,
        body: {
          "booking_id": request.bookingId,
        },
      );

      Navigator.pop(context);

      final data = jsonDecode(response.body);

      if (data["success"] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              "Booking Approved",
              style: GoogleFonts.inter(),
            ),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );

        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => PendingRequestsPage(ownerId: ownerId),
          ),
        );
      } else {
        _showError(context, data["message"]);
      }
    } catch (_) {
      Navigator.pop(context);
      _showError(context, "Network error occurred");
    }
  }

  void _showError(BuildContext context, String? message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          message ?? "Something went wrong",
          style: GoogleFonts.inter(),
        ),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }
}
