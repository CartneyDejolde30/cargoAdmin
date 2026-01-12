class Booking {
  final String? id;
  final String userId;
  final int carId;
  final String carName;
  final String carImage;
  final String location;
  
  // User Information
  final String fullName;
  final String email;
  final String contactNumber;
  final String gender;
  
  // Booking Details
  final bool bookWithDriver;
  final String rentalPeriod; // 'Day', 'Weekly', 'Monthly'
  final DateTime pickupDate;
  final DateTime returnDate;
  final String pickupTime; // Store as "HH:mm" format
  final String returnTime;
  
  // Pricing
  final double pricePerDay;
  final double driverFee;
  final int numberOfDays;
  final double totalAmount;
  
  // Status & Metadata
  final String status; // 'pending', 'confirmed', 'active', 'completed', 'cancelled'
  final DateTime createdAt;
  final DateTime? updatedAt;
  final String? paymentId;
  final String? paymentMethod;

  Booking({
    this.id,
    required this.userId,
    required this.carId,
    required this.carName,
    required this.carImage,
    required this.location,
    required this.fullName,
    required this.email,
    required this.contactNumber,
    required this.gender,
    required this.bookWithDriver,
    required this.rentalPeriod,
    required this.pickupDate,
    required this.returnDate,
    required this.pickupTime,
    required this.returnTime,
    required this.pricePerDay,
    required this.driverFee,
    required this.numberOfDays,
    required this.totalAmount,
    this.status = 'pending',
    required this.createdAt,
    this.updatedAt,
    this.paymentId,
    this.paymentMethod,
  });

  // Convert to JSON for database storage
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'car_id': carId,
      'car_name': carName,
      'car_image': carImage,
      'location': location,
      'full_name': fullName,
      'email': email,
      'contact': contactNumber,
      'gender': gender,
      'book_with_driver': bookWithDriver,
      'rental_period': rentalPeriod,
      'pickup_date': pickupDate.toIso8601String(),
      'return_date': returnDate.toIso8601String(),
      'pickup_time': pickupTime,
      'return_time': returnTime,
      'price_per_day': pricePerDay,
      'driver_fee': driverFee,
      'number_of_days': numberOfDays,
      'total_amount': totalAmount,
      'status': status,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'payment_id': paymentId,
      'payment_method': paymentMethod,
    };
  }

  // Create from JSON (from database)
  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id']?.toString(),
      userId: json['user_id']?.toString() ?? '',
      carId: json['car_id'] ?? 0,
      carName: json['car_name'] ?? '',
      carImage: json['car_image'] ?? '',
      location: json['location'] ?? '',
      fullName: json['full_name'] ?? '',
      email: json['email'] ?? '',
      contactNumber: json['contact'] ?? '',
      gender: json['gender'] ?? 'Male',
      bookWithDriver: json['book_with_driver'] ?? false,
      rentalPeriod: json['rental_period'] ?? 'Day',
      pickupDate: DateTime.parse(json['pickup_date']),
      returnDate: DateTime.parse(json['return_date']),
      pickupTime: json['pickup_time'] ?? '09:00',
      returnTime: json['return_time'] ?? '17:00',
      pricePerDay: (json['price_per_day'] ?? 0).toDouble(),
      driverFee: (json['driver_fee'] ?? 0).toDouble(),
      numberOfDays: json['number_of_days'] ?? 1,
      totalAmount: (json['total_amount'] ?? 0).toDouble(),
      status: json['status'] ?? 'pending',
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: json['updated_at'] != null 
          ? DateTime.parse(json['updated_at']) 
          : null,
      paymentId: json['payment_id']?.toString(),
      paymentMethod: json['payment_method'],
    );
  }

  // Create a copy with updated fields
  Booking copyWith({
    String? id,
    String? userId,
    int? carId,
    String? carName,
    String? carImage,
    String? location,
    String? fullName,
    String? email,
    String? contactNumber,
    String? gender,
    bool? bookWithDriver,
    String? rentalPeriod,
    DateTime? pickupDate,
    DateTime? returnDate,
    String? pickupTime,
    String? returnTime,
    double? pricePerDay,
    double? driverFee,
    int? numberOfDays,
    double? totalAmount,
    String? status,
    DateTime? createdAt,
    DateTime? updatedAt,
    String? paymentId,
    String? paymentMethod,
  }) {
    return Booking(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      carId: carId ?? this.carId,
      carName: carName ?? this.carName,
      carImage: carImage ?? this.carImage,
      location: location ?? this.location,
      fullName: fullName ?? this.fullName,
      email: email ?? this.email,
      contactNumber: contactNumber ?? this.contactNumber,
      gender: gender ?? this.gender,
      bookWithDriver: bookWithDriver ?? this.bookWithDriver,
      rentalPeriod: rentalPeriod ?? this.rentalPeriod,
      pickupDate: pickupDate ?? this.pickupDate,
      returnDate: returnDate ?? this.returnDate,
      pickupTime: pickupTime ?? this.pickupTime,
      returnTime: returnTime ?? this.returnTime,
      pricePerDay: pricePerDay ?? this.pricePerDay,
      driverFee: driverFee ?? this.driverFee,
      numberOfDays: numberOfDays ?? this.numberOfDays,
      totalAmount: totalAmount ?? this.totalAmount,
      status: status ?? this.status,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      paymentId: paymentId ?? this.paymentId,
      paymentMethod: paymentMethod ?? this.paymentMethod,
    );
  }
}