import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'car_listing.dart';

Future<bool> submitVehicleListing({
  required CarListing listing,
  required File? mainPhoto,
  required File? orFile,
  required File? crFile,
  required List<File> extraPhotos,
  required String vehicleType, // 'car' or 'motorcycle'
}) async {
  // Use different endpoints based on vehicle type
  final endpoint = vehicleType == 'motorcycle' 
      ? "motorcycles_api.php" 
      : "cars_api.php";
  
  final url = Uri.parse("http://10.139.150.2/carGOAdmin/$endpoint");

  final request = http.MultipartRequest("POST", url);

  // Required action for API
  request.fields["action"] = "insert";

  // -------- TEXT FIELDS --------
  final fields = {
    "owner_id": listing.owner.toString(),
    "status": "pending", // Always pending for admin approval
    "brand": listing.brand ?? "",
    "model": listing.model ?? "",
    "body_style": listing.bodyStyle ?? "",
    "plate_number": listing.plateNumber ?? "",
    "color": listing.color ?? "",
    "description": listing.description ?? "",
    "advance_notice": listing.advanceNotice ?? "",
    "min_trip_duration": listing.minTripDuration ?? "",
    "max_trip_duration": listing.maxTripDuration ?? "",
    "delivery_types": jsonEncode(listing.deliveryTypes),
    "features": jsonEncode(listing.features),
    "rules": jsonEncode(listing.rules),
    "has_unlimited_mileage": listing.hasUnlimitedMileage ? "1" : "0",
    "price_per_day": listing.dailyRate.toString(),
    "location": listing.location ?? "",
    "latitude": listing.latitude.toString(),
    "longitude": listing.longitude.toString(),
  };

  // Vehicle-specific fields
  if (vehicleType == 'motorcycle') {
    fields["motorcycle_year"] = listing.year ?? "";
    fields["trim"] = listing.trim ?? ""; // engine_displacement
  } else {
    fields["year"] = listing.year ?? "";
    fields["trim"] = listing.trim ?? "";
  }

  request.fields.addAll(fields);

  print("🚗 Sending ${vehicleType.toUpperCase()} Data: ${request.fields}");

  // -------- FILE UPLOADS --------
  if (mainPhoto != null) {
    request.files.add(await http.MultipartFile.fromPath("image", mainPhoto.path));
    print("📤 Main Photo Attached");
  }

  if (orFile != null) {
    request.files.add(await http.MultipartFile.fromPath("official_receipt", orFile.path));
    print("📤 Official Receipt Uploaded");
  }

  if (crFile != null) {
    request.files.add(await http.MultipartFile.fromPath("certificate_of_registration", crFile.path));
    print("📤 Certificate of Registration Uploaded");
  }

  if (extraPhotos.isNotEmpty) {
    for (var file in extraPhotos) {
      request.files.add(await http.MultipartFile.fromPath("extra_photos[]", file.path));
    }
    print("📤 Extra Images Uploaded: ${extraPhotos.length}");
  }

  // -------- SEND REQUEST --------
  try {
    final response = await request.send();
    final responseBody = await response.stream.bytesToString();

    print("🔍 STATUS CODE: ${response.statusCode}");
    print("📌 SERVER RESPONSE: $responseBody");

    if (response.statusCode == 200) {
      final jsonResp = jsonDecode(responseBody);

      if (jsonResp["success"] == true) {
        print("✅ ${vehicleType.toUpperCase()} Upload Successful!");
        return true;
      } else {
        print("❌ Upload Failed: ${jsonResp["message"]}");
      }
    } else {
      print("❌ Server rejected upload (Code ${response.statusCode})");
    }

    return false;

  } catch (err) {
    print("🛑 ERROR DURING UPLOAD: $err");
    return false;
  }
}

// Keep old function for backwards compatibility
Future<bool> submitCarListing({
  required CarListing listing,
  required File? mainPhoto,
  required File? orFile,
  required File? crFile,
  required List<File> extraPhotos,
}) async {
  return submitVehicleListing(
    listing: listing,
    mainPhoto: mainPhoto,
    orFile: orFile,
    crFile: crFile,
    extraPhotos: extraPhotos,
    vehicleType: 'car',
  );
}