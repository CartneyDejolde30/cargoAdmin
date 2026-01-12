import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import './booking_model.dart';

class BookingService {
  static const String baseUrl = 'http://10.139.150.2/carGOAdmin/';
  
  /* ---------------- FETCH RECENT BOOKINGS ---------------- */
  Future<List<Booking>> fetchRecentBookings(String ownerId, {int limit = 5}) async {
    try {
      final url = Uri.parse("${baseUrl}api/dashboard/recent_bookings.php?owner_id=$ownerId&limit=$limit");
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['bookings'] is List) {
          return (data['bookings'] as List)
              .map((booking) => Booking.fromJson(booking))
              .toList();
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching recent bookings: $e");
    }

    return [];
  }

  /* ---------------- FETCH UPCOMING BOOKINGS ---------------- */
  Future<List<Booking>> fetchUpcomingBookings(String ownerId) async {
    try {
      final url = Uri.parse("${baseUrl}api/dashboard/upcoming_bookings.php?owner_id=$ownerId");
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['bookings'] is List) {
          return (data['bookings'] as List)
              .map((booking) => Booking.fromJson(booking))
              .toList();
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching upcoming bookings: $e");
    }

    return [];
  }

  /* ---------------- FETCH PENDING REQUESTS ---------------- */
  Future<List<Map<String, dynamic>>> fetchPendingRequests(String ownerId) async {
    try {
      final url = Uri.parse("${baseUrl}api/get_owner_pending_requests.php?owner_id=$ownerId");
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      debugPrint("📡 Pending Requests API: $url");
      debugPrint("📥 Response: ${response.body}");

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['requests'] is List) {
          return List<Map<String, dynamic>>.from(data['requests']);
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching pending requests: $e");
    }

    return [];
  }

  /* ---------------- FETCH ACTIVE BOOKINGS ---------------- */
  Future<List<Map<String, dynamic>>> fetchActiveBookings(String ownerId) async {
    try {
      final url = Uri.parse("${baseUrl}api/get_owner_active_bookings.php?owner_id=$ownerId");
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      debugPrint("📡 Active Bookings API: $url");
      debugPrint("📥 Response: ${response.body}");

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['bookings'] is List) {
          return List<Map<String, dynamic>>.from(data['bookings']);
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching active bookings: $e");
    }

    return [];
  }

  /* ---------------- APPROVE BOOKING ---------------- */
  Future<Map<String, dynamic>> approveBooking(String bookingId, String ownerId) async {
    try {
      final url = Uri.parse("${baseUrl}api/approve_booking.php");
      final response = await http.post(
        url,
        body: {
          'booking_id': bookingId,
          'owner_id': ownerId,
        },
      ).timeout(const Duration(seconds: 10));

      debugPrint("📡 Approve Booking API: $url");
      debugPrint("📤 Body: booking_id=$bookingId, owner_id=$ownerId");
      debugPrint("📥 Response: ${response.body}");

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (e) {
      debugPrint("❌ Error approving booking: $e");
    }

    return {'success': false, 'message': 'Network error'};
  }

  /* ---------------- REJECT BOOKING ---------------- */
  Future<Map<String, dynamic>> rejectBooking(
    String bookingId, 
    String ownerId, 
    String reason
  ) async {
    try {
      final url = Uri.parse("${baseUrl}api/reject_booking.php");
      final response = await http.post(
        url,
        body: {
          'booking_id': bookingId,
          'owner_id': ownerId,
          'reason': reason,
        },
      ).timeout(const Duration(seconds: 10));

      debugPrint("📡 Reject Booking API: $url");
      debugPrint("📥 Response: ${response.body}");

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (e) {
      debugPrint("❌ Error rejecting booking: $e");
    }

    return {'success': false, 'message': 'Network error'};
  }

  /* ---------------- END TRIP/MARK AS COMPLETED ---------------- */
  Future<Map<String, dynamic>> endTrip(String bookingId, String ownerId) async {
    try {
      final url = Uri.parse("${baseUrl}api/end_trip.php");
      final response = await http.post(
        url,
        body: {
          'booking_id': bookingId,
          'owner_id': ownerId,
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (e) {
      debugPrint("❌ Error ending trip: $e");
    }

    return {'success': false, 'message': 'Network error'};
  }
}