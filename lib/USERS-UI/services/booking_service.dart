import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_application_1/USERS-UI/Renter/models/booking.dart';

class BookingService {

  // =========================
  // GET MY BOOKINGS (REAL DATA)
  // =========================
  static Future<List<Booking>> getMyBookings(int userId) async {
  final url = Uri.parse(
    "http://10.139.150.2/carGOAdmin/api/get_my_bookings.php?user_id=$userId",
  );

  final response = await http.get(url);
  final data = jsonDecode(response.body);

  if (data['success'] != true) {
    throw Exception(data['message'] ?? 'Failed to load bookings');
  }

  final List bookingsJson = data['bookings']; // ✅ LIST

  return bookingsJson
      .map((e) => Booking.fromJson(e))
      .toList();
}


  // =========================
  // CANCEL BOOKING
  // =========================
  static Future<bool> cancelBooking({
    required int bookingId,
    required int userId,
    
  }) async {
    
    final response = await http.post(
      
      Uri.parse(
        'http://10.139.150.2/carGOAdmin/api/cancel_booking.php',
      ),
      body: {
        'booking_id': bookingId.toString(),
        'user_id': userId,
        
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['success'] == true;
    }
    return false;
  }
}
