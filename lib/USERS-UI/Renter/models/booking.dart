class Booking {
 final int carId;
  final int ownerId;
  final String ownerName;
  final String ownerImage;
  final int bookingId;
  final String carName;
  final String carImage;
  final String location;

  final String pickupDate;
  final String pickupTime;
  final String returnDate;
  final String returnTime;

  final String totalPrice;
  final String status;

  Booking({
    required this.carId,
    required this.ownerId,
    required this.ownerName,
    required this.ownerImage,
    required this.bookingId,
    required this.carName,
    required this.carImage,
    required this.location,
    required this.pickupDate,
    required this.pickupTime,
    required this.returnDate,
    required this.returnTime,
    required this.totalPrice,
    required this.status,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
  return Booking(
    bookingId: json['bookingId'] ?? 0,
    carId: json['carId'] ?? 0,
    ownerId: json['ownerId'] ?? 0,

    carName: json['carName'] ?? '',
    carImage: json['carImage'] ?? '',
    ownerName: json['ownerName'] ?? '',
    ownerImage: json['ownerImage'] ?? '', 
    location: json['location'] ?? '',
    pickupDate: json['pickupDate'] ?? '',
    pickupTime: json['pickupTime'] ?? '',
    returnDate: json['returnDate'] ?? '',
    returnTime: json['returnTime'] ?? '',

    totalPrice: json['totalPrice'].toString(),
    status: json['status'] ?? '',
  );
}

}
