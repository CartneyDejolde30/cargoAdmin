import 'package:flutter/material.dart';
import 'booking_screen.dart';

class MotorcycleBookingScreen extends StatelessWidget {
  final int motorcycleId;
  final String motorcycleName;
  final String motorcycleImage;
  final String pricePerDay;
  final String location;
  final String ownerId;
  final String? userId;
  final String? userFullName;
  final String? userEmail;
  final String? userContact;
  final String? userMunicipality;
  final double? ownerLatitude;
  final double? ownerLongitude;

  const MotorcycleBookingScreen({
    super.key,
    required this.motorcycleId,
    required this.motorcycleName,
    required this.motorcycleImage,
    required this.pricePerDay,
    required this.location,
    required this.ownerId,
    this.userId,
    this.userFullName,
    this.userEmail,
    this.userContact,
    this.userMunicipality,
    this.ownerLatitude,
    this.ownerLongitude,
  });

  @override
  Widget build(BuildContext context) {
    return BookingScreen(
      carId: motorcycleId, // reuse carId
      carName: motorcycleName,
      carImage: motorcycleImage,
      pricePerDay: pricePerDay,
      location: location,
      ownerId: ownerId,
      userId: userId,
      userFullName: userFullName,
      userEmail: userEmail,
      userContact: userContact,
      userMunicipality: userMunicipality,
      ownerLatitude: ownerLatitude,
      ownerLongitude: ownerLongitude,
    );
  }
}
