// lib/USERS-UI/Owner/dashboard/booking_model.dart

class Booking {
  final int id;
  final String carName;
  final String carImage;
  final String carFullName;
  final String renterName;
  final String startDate;
  final String endDate;
  final String status;
  final String totalAmount;
  final String rentalPeriod;

  Booking({
    required this.id,
    required this.carName,
    required this.carImage,
    required this.carFullName,
    required this.renterName,
    required this.startDate,
    required this.endDate,
    required this.status,
    required this.totalAmount,
    required this.rentalPeriod,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    // Get car brand and model for full name
    final brand = json['brand']?.toString() ?? '';
    final model = json['model']?.toString() ?? '';
    final carFullName = brand.isNotEmpty && model.isNotEmpty 
        ? '$brand $model' 
        : json['car_name']?.toString() ?? 'Unknown Car';

    return Booking(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      carName: json['car_name']?.toString() ?? '',
      carImage: json['car_image']?.toString() ?? '',
      carFullName: carFullName,
      renterName: json['full_name']?.toString() ?? 'Unknown Renter',
      startDate: json['pickup_date']?.toString() ?? '',
      endDate: json['return_date']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      totalAmount: json['total_amount']?.toString() ?? '0',
      rentalPeriod: json['rental_period']?.toString() ?? 'Day',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'car_name': carName,
      'car_image': carImage,
      'car_full_name': carFullName,
      'full_name': renterName,
      'pickup_date': startDate,
      'return_date': endDate,
      'status': status,
      'total_amount': totalAmount,
      'rental_period': rentalPeriod,
    };
  }
}