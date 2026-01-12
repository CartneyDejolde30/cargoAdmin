class BookingRequest {
  final String bookingId;
  final String carName;
  final String carImage;
  final String totalAmount;
  final String pickupDate;
  final String returnDate;
  final String rentalPeriod;
  final String fullName;
  final String contact;
  final String email;
  final String location;
  final String seats;
  final String transmission;

  BookingRequest({
    required this.bookingId,
    required this.carName,
    required this.carImage,
    required this.totalAmount,
    required this.pickupDate,
    required this.returnDate,
    required this.rentalPeriod,
    required this.fullName,
    required this.contact,
    required this.email,
    required this.location,
    required this.seats,
    required this.transmission,
  });

  factory BookingRequest.fromJson(Map<String, dynamic> json) {
    return BookingRequest(
      bookingId: json['booking_id']?.toString() ?? '',
      carName: json['car_name'] ?? 'Unknown Car',
      carImage: json['car_image'] ?? '',
      totalAmount: json['total_amount']?.toString() ?? '0',
      pickupDate: json['pickup_date'] ?? '',
      returnDate: json['return_date'] ?? '',
      rentalPeriod: json['rental_period'] ?? '',
      fullName: json['full_name'] ?? '',
      contact: json['contact'] ?? '',
      email: json['email'] ?? '',
      location: json['location'] ?? 'N/A',
      seats: json['seats'] ?? 'N/A',
      transmission: json['transmission'] ?? 'N/A',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'booking_id': bookingId,
      'car_name': carName,
      'car_image': carImage,
      'total_amount': totalAmount,
      'pickup_date': pickupDate,
      'return_date': returnDate,
      'rental_period': rentalPeriod,
      'full_name': fullName,
      'contact': contact,
      'email': email,
      'location': location,
      'seats': seats,
      'transmission': transmission,
    };
  }
}