//import 'package:supabase_flutter/supabase_flutter.dart';

/*class BookingService {
 final SupabaseClient _supabase = Supabase.instance.client;

  // Create a new booking
  Future<Booking?> createBooking(Booking booking) async {
    try {
      final response = await _supabase
          .from('bookings')
          .insert(booking.toJson())
          .select()
          .single();

      return Booking.fromJson(response);
    } catch (e) {
      print('Error creating booking: $e');
      return null;
    }
  }

  // Get all bookings for a user
  Future<List<Booking>> getUserBookings(String userId) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select()
          .eq('user_id', userId)
          .order('created_at', ascending: false);

      return (response as List)
          .map((booking) => Booking.fromJson(booking))
          .toList();
    } catch (e) {
      print('Error fetching user bookings: $e');
      return [];
    }
  }

  // Get a single booking by ID
  Future<Booking?> getBookingById(String bookingId) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select()
          .eq('id', bookingId)
          .single();

      return Booking.fromJson(response);
    } catch (e) {
      print('Error fetching booking: $e');
      return null;
    }
  }

  // Get active bookings (current rentals)
  Future<List<Booking>> getActiveBookings(String userId) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select()
          .eq('user_id', userId)
          .in_('status', ['confirmed', 'active'])
          .order('pickup_date', ascending: true);

      return (response as List)
          .map((booking) => Booking.fromJson(booking))
          .toList();
    } catch (e) {
      print('Error fetching active bookings: $e');
      return [];
    }
  }

  // Get booking history (completed or cancelled)
  Future<List<Booking>> getBookingHistory(String userId) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select()
          .eq('user_id', userId)
          .in_('status', ['completed', 'cancelled'])
          .order('created_at', ascending: false);

      return (response as List)
          .map((booking) => Booking.fromJson(booking))
          .toList();
    } catch (e) {
      print('Error fetching booking history: $e');
      return [];
    }
  }

  // Update booking status
  Future<bool> updateBookingStatus(String bookingId, String status) async {
    try {
      await _supabase
          .from('bookings')
          .update({
            'status': status,
            'updated_at': DateTime.now().toIso8601String(),
          })
          .eq('id', bookingId);

      return true;
    } catch (e) {
      print('Error updating booking status: $e');
      return false;
    }
  }

  // Update payment information
  Future<bool> updatePaymentInfo({
    required String bookingId,
    required String paymentId,
    required String paymentMethod,
  }) async {
    try {
      await _supabase
          .from('bookings')
          .update({
            'payment_id': paymentId,
            'payment_method': paymentMethod,
            'status': 'confirmed',
            'updated_at': DateTime.now().toIso8601String(),
          })
          .eq('id', bookingId);

      return true;
    } catch (e) {
      print('Error updating payment info: $e');
      return false;
    }
  }

  // Cancel a booking
  Future<bool> cancelBooking(String bookingId) async {
    try {
      await _supabase
          .from('bookings')
          .update({
            'status': 'cancelled',
            'updated_at': DateTime.now().toIso8601String(),
          })
          .eq('id', bookingId);

      return true;
    } catch (e) {
      print('Error cancelling booking: $e');
      return false;
    }
  }

  // Check if a car is available for specific dates
  Future<bool> isCarAvailable({
    required int carId,
    required DateTime pickupDate,
    required DateTime returnDate,
  }) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select()
          .eq('car_id', carId)
          .in_('status', ['confirmed', 'active'])
          .or('pickup_date.lte.${returnDate.toIso8601String()},return_date.gte.${pickupDate.toIso8601String()}');

      // If there are any overlapping bookings, car is not available
      return (response as List).isEmpty;
    } catch (e) {
      print('Error checking car availability: $e');
      return false;
    }
  }

  // Get upcoming bookings (next 7 days)
  Future<List<Booking>> getUpcomingBookings(String userId) async {
    try {
      final now = DateTime.now();
      final next7Days = now.add(Duration(days: 7));

      final response = await _supabase
          .from('bookings')
          .select()
          .eq('user_id', userId)
          .eq('status', 'confirmed')
          .gte('pickup_date', now.toIso8601String())
          .lte('pickup_date', next7Days.toIso8601String())
          .order('pickup_date', ascending: true);

      return (response as List)
          .map((booking) => Booking.fromJson(booking))
          .toList();
    } catch (e) {
      print('Error fetching upcoming bookings: $e');
      return [];
    }
  }

  // Delete a booking (admin function)
  Future<bool> deleteBooking(String bookingId) async {
    try {
      await _supabase
          .from('bookings')
          .delete()
          .eq('id', bookingId);

      return true;
    } catch (e) {
      print('Error deleting booking: $e');
      return false;
    }
  }

  // Get total revenue from bookings (admin/owner function)
  Future<double> getTotalRevenue() async {
    try {
      final response = await _supabase
          .from('bookings')
          .select('total_amount')
          .in_('status', ['confirmed', 'active', 'completed']);

      double total = 0;
      for (var booking in response) {
        total += (booking['total_amount'] ?? 0).toDouble();
      }

      return total;
    } catch (e) {
      print('Error calculating total revenue: $e');
      return 0;
    }
  }

  // Get bookings count by status
  Future<Map<String, int>> getBookingStatistics(String userId) async {
    try {
      final response = await _supabase
          .from('bookings')
          .select('status')
          .eq('user_id', userId);

      Map<String, int> stats = {
        'pending': 0,
        'confirmed': 0,
        'active': 0,
        'completed': 0,
        'cancelled': 0,
      };

      for (var booking in response) {
        String status = booking['status'] ?? 'pending';
        stats[status] = (stats[status] ?? 0) + 1;
      }

      return stats;
    } catch (e) {
      print('Error fetching booking statistics: $e');
      return {};
    }
  }
}*/