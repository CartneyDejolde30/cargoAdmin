class TempBookingData {
  // Active bookings - currently renting
  static List<Map<String, dynamic>> activeBookings = [
    {
      'bookingId': 'BK001',
      'carName': 'Toyota Camry 2023',
      'carImage': 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=400',
      'location': 'Midsayap, Cotabato',
      'pickupDate': 'Dec 10, 2024',
      'pickupTime': '10:00 AM',
      'returnDate': 'Dec 15, 2024',
      'returnTime': '10:00 AM',
      'totalPrice': '12,500',
      'status': 'active',
    },
    {
      'bookingId': 'BK002',
      'carName': 'Honda Civic 2024',
      'carImage': 'https://images.unsplash.com/photo-1590362891991-f776e747a588?w=400',
      'location': 'Kidapawan City',
      'pickupDate': 'Dec 11, 2024',
      'pickupTime': '2:00 PM',
      'returnDate': 'Dec 13, 2024',
      'returnTime': '2:00 PM',
      'totalPrice': '6,000',
      'status': 'active',
    },
  ];

  // Pending bookings - awaiting payment
  static List<Map<String, dynamic>> pendingBookings = [
    {
      'bookingId': 'BK003',
      'carName': 'Mitsubishi Montero Sport',
      'carImage': 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=400',
      'location': 'Koronadal City',
      'pickupDate': 'Dec 16, 2024',
      'pickupTime': '9:00 AM',
      'returnDate': 'Dec 20, 2024',
      'returnTime': '9:00 AM',
      'totalPrice': '18,000',
      'status': 'pending',
    },
    {
      'bookingId': 'BK004',
      'carName': 'Nissan Navara 2023',
      'carImage': 'https://images.unsplash.com/photo-1594502881417-8eae30d0a318?w=400',
      'location': 'General Santos City',
      'pickupDate': 'Dec 18, 2024',
      'pickupTime': '11:00 AM',
      'returnDate': 'Dec 22, 2024',
      'returnTime': '11:00 AM',
      'totalPrice': '15,000',
      'status': 'pending',
    },
    {
      'bookingId': 'BK005',
      'carName': 'Suzuki Ertiga 2024',
      'carImage': 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=400',
      'location': 'Tacurong City',
      'pickupDate': 'Dec 19, 2024',
      'pickupTime': '1:00 PM',
      'returnDate': 'Dec 21, 2024',
      'returnTime': '1:00 PM',
      'totalPrice': '5,400',
      'status': 'pending',
    },
  ];

  // Upcoming bookings - confirmed, scheduled for future
  static List<Map<String, dynamic>> upcomingBookings = [
    {
      'bookingId': 'BK006',
      'carName': 'Ford Ranger Raptor',
      'carImage': 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=400',
      'location': 'Cotabato City',
      'pickupDate': 'Dec 25, 2024',
      'pickupTime': '8:00 AM',
      'returnDate': 'Dec 30, 2024',
      'returnTime': '8:00 AM',
      'totalPrice': '35,000',
      'status': 'upcoming',
    },
    {
      'bookingId': 'BK007',
      'carName': 'Hyundai Tucson 2024',
      'carImage': 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=400',
      'location': 'Midsayap, Cotabato',
      'pickupDate': 'Jan 2, 2025',
      'pickupTime': '10:00 AM',
      'returnDate': 'Jan 5, 2025',
      'returnTime': '10:00 AM',
      'totalPrice': '12,000',
      'status': 'upcoming',
    },
    {
      'bookingId': 'BK008',
      'carName': 'Mazda CX-5 2023',
      'carImage': 'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?w=400',
      'location': 'Kidapawan City',
      'pickupDate': 'Jan 8, 2025',
      'pickupTime': '3:00 PM',
      'returnDate': 'Jan 12, 2025',
      'returnTime': '3:00 PM',
      'totalPrice': '16,000',
      'status': 'upcoming',
    },
    {
      'bookingId': 'BK009',
      'carName': 'Kia Sportage 2024',
      'carImage': 'https://images.unsplash.com/photo-1600705722908-bab1e61c0b4d?w=400',
      'location': 'General Santos City',
      'pickupDate': 'Jan 15, 2025',
      'pickupTime': '12:00 PM',
      'returnDate': 'Jan 17, 2025',
      'returnTime': '12:00 PM',
      'totalPrice': '7,000',
      'status': 'upcoming',
    },
  ];

  // Past bookings - completed
  static List<Map<String, dynamic>> pastBookings = [
    {
      'bookingId': 'BK010',
      'carName': 'Toyota Fortuner 2023',
      'carImage': 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400',
      'location': 'Koronadal City',
      'pickupDate': 'Nov 20, 2024',
      'pickupTime': '9:00 AM',
      'returnDate': 'Nov 25, 2024',
      'returnTime': '9:00 AM',
      'totalPrice': '22,500',
      'status': 'past',
      'has_reviewed': false,
    },
    {
      'bookingId': 'BK011',
      'carName': 'Honda CR-V 2023',
      'carImage': 'https://images.unsplash.com/photo-1581540222194-0def2dda95b8?w=400',
      'location': 'Midsayap, Cotabato',
      'pickupDate': 'Nov 10, 2024',
      'pickupTime': '2:00 PM',
      'returnDate': 'Nov 13, 2024',
      'returnTime': '2:00 PM',
      'totalPrice': '10,500',
      'status': 'past',
      'has_reviewed': false,
    },
    {
      'bookingId': 'BK012',
      'carName': 'Isuzu MU-X 2024',
      'carImage': 'https://images.unsplash.com/photo-1611859266238-4b98091d9d9b?w=400',
      'location': 'Tacurong City',
      'pickupDate': 'Oct 28, 2024',
      'pickupTime': '11:00 AM',
      'returnDate': 'Nov 1, 2024',
      'returnTime': '11:00 AM',
      'totalPrice': '18,000',
      'status': 'past',
      'has_reviewed': false,
    },
    {
      'bookingId': 'BK013',
      'carName': 'Chevrolet Trailblazer',
      'carImage': 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?w=400',
      'location': 'General Santos City',
      'pickupDate': 'Oct 15, 2024',
      'pickupTime': '8:00 AM',
      'returnDate': 'Oct 18, 2024',
      'returnTime': '8:00 AM',
      'totalPrice': '12,000',
      'status': 'past',
      'has_reviewed': false,
    },
    {
      'bookingId': 'BK014',
      'carName': 'Subaru Forester 2023',
      'carImage': 'https://images.unsplash.com/photo-1610768764270-790fbec18178?w=400',
      'location': 'Cotabato City',
      'pickupDate': 'Oct 5, 2024',
      'pickupTime': '1:00 PM',
      'returnDate': 'Oct 7, 2024',
      'returnTime': '1:00 PM',
      'totalPrice': '7,000',
      'status': 'past',
      'has_reviewed': false,
    },
  ];

  // Helper method to get all bookings
  static List<Map<String, dynamic>> getAllBookings() {
    return [
      ...activeBookings,
      ...pendingBookings,
      ...upcomingBookings,
      ...pastBookings,
    ];
  }

  // Helper method to get booking by ID
  static Map<String, dynamic>? getBookingById(String bookingId) {
    try {
      return getAllBookings().firstWhere(
        (booking) => booking['bookingId'] == bookingId,
      );
    } catch (e) {
      return null;
    }
  }

  // Helper method to clear all bookings (for testing empty state)
  static void clearAllBookings() {
    activeBookings.clear();
    pendingBookings.clear();
    upcomingBookings.clear();
    pastBookings.clear();
  }

  // 🐛 DEBUG: Method to restore sample data (useful after testing empty state)
  static void restoreSampleData() {
    // Re-populate with sample data by calling the original initialization
    // This is a simple way to reset - in production you'd fetch from API
  }
}