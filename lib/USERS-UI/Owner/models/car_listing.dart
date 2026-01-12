import 'dart:convert';

class CarListing {
  int? carId;
  int? owner;
  String? carStatus;

  // ------------------ BASIC INFO ------------------
  String? year;
  String? brand;
  String? model;
  String? bodyStyle;
  String? trim;
  String? plateNumber;
  String? color;
  String? description;

  // ------------------ RENTAL SETTINGS ------------------
  String? advanceNotice;
  String? minTripDuration;
  String? maxTripDuration;
  List<String> deliveryTypes;

  // ------------------ FEATURES & RULES ------------------
  List<String> features;
  List<String> rules;
  bool hasUnlimitedMileage;
  int? mileageLimit;

  // ------------------ PRICING ------------------
  double? dailyRate;

  // ------------------ LOCATION ------------------
  String? location;
  double? latitude;
  double? longitude;

  // ------------------ DOCUMENTS ------------------
  String? officialReceipt;
  String? certificateOfRegistration;

  // ------------------ PHOTOS ------------------
  List<String> photoUrls;
  Map<int, String> carPhotos; // Temporary unsaved images


  CarListing({
    this.carId,
    this.owner,
    this.carStatus,
    this.year,
    this.brand,
    this.model,
    this.bodyStyle,
    this.trim,
    this.plateNumber,
    this.color,
    this.description,
    this.advanceNotice,
    this.minTripDuration,
    this.maxTripDuration,
    List<String>? deliveryTypes,
    List<String>? features,
    List<String>? rules,
    this.hasUnlimitedMileage = true,
    this.mileageLimit,
    this.dailyRate,
    this.location,
    this.latitude,
    this.longitude,
    this.officialReceipt,
    this.certificateOfRegistration,
    List<String>? photoUrls,
    Map<int, String>? carPhotos,
  })  : deliveryTypes = deliveryTypes ?? [],
        features = features ?? [],
        rules = rules ?? [],
        photoUrls = photoUrls ?? [],
        carPhotos = carPhotos ?? {};


  // ------------------ PARSER ------------------

  factory CarListing.fromJson(Map<String, dynamic> json) {
    List<String> parseList(dynamic value) {
      if (value == null) return [];
      if (value is List) return List<String>.from(value);
      try {
        return List<String>.from(jsonDecode(value));
      } catch (_) {
        return [];
      }
    }

    return CarListing(
      carId: int.tryParse(json["id"]?.toString() ?? ""),
      owner: int.tryParse(json["owner_id"]?.toString() ?? ""),
      carStatus: json["status"],
      year: json["car_year"]?.toString(),
      brand: json["brand"],
      model: json["model"],
      bodyStyle: json["body_style"],
      trim: json["trim"],
      plateNumber: json["plate_number"],
      color: json["color"],
      description: json["description"],

      advanceNotice: json["advance_notice"],
      minTripDuration: json["min_trip_duration"],
      maxTripDuration: json["max_trip_duration"],

      deliveryTypes: parseList(json["delivery_types"]),
      features: parseList(json["features"]),
      rules: parseList(json["rules"]),

      hasUnlimitedMileage: json["has_unlimited_mileage"] == "1",
      mileageLimit: int.tryParse(json["mileage_limit"]?.toString() ?? ""),

      dailyRate: double.tryParse(json["price_per_day"]?.toString() ?? ""),
      location: json["location"],
      latitude: double.tryParse(json["latitude"]?.toString() ?? ""),
      longitude: double.tryParse(json["longitude"]?.toString() ?? ""),

      officialReceipt: json["official_receipt"],
      certificateOfRegistration: json["certificate_of_registration"],

      photoUrls: parseList(json["extra_images"]),
    );
  }


  // ------------------ API FIELD SERIALIZER ------------------

  Map<String, String> toApiFields() {
    return {
      if (carId != null) "id": carId.toString(),
      if (owner != null) "owner_id": owner.toString(),
      if (carStatus != null) "status": carStatus!,
      if (year != null) "year": year!,
      if (brand != null) "brand": brand!,
      if (model != null) "model": model!,
      if (bodyStyle != null) "body_style": bodyStyle!,
      if (trim != null) "trim": trim!,
      if (plateNumber != null) "plate_number": plateNumber!,
      if (color != null) "color": color!,
      if (description != null) "description": description!,

      if (advanceNotice != null) "advance_notice": advanceNotice!,
      if (minTripDuration != null) "min_trip_duration": minTripDuration!,
      if (maxTripDuration != null) "max_trip_duration": maxTripDuration!,

      "delivery_types": jsonEncode(deliveryTypes),
      "features": jsonEncode(features),
      "rules": jsonEncode(rules),

      "has_unlimited_mileage": hasUnlimitedMileage ? "1" : "0",
      if (mileageLimit != null) "mileage_limit": mileageLimit.toString(),
      if (dailyRate != null) "price_per_day": dailyRate.toString(),

      if (location != null) "location": location!,
      if (latitude != null) "latitude": latitude.toString(),
      if (longitude != null) "longitude": longitude.toString(),

      if (officialReceipt != null) "official_receipt": officialReceipt!,
    if (certificateOfRegistration != null) "certificate_of_registration": certificateOfRegistration!,

      "photo_urls": jsonEncode(photoUrls),
    };
  }


  // ------------------ EXTRAS ------------------

  String? get mainPhotoUrl => photoUrls.isNotEmpty ? photoUrls.first : null;

  void addUploadedPhoto(String path) => photoUrls.add(path);

  void removeCarPhoto(int spotIndex) => carPhotos.remove(spotIndex);
}
