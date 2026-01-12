class UserVerification {
  int? userId;
  String? firstName;
  String? lastName;
  String? email;
  String? mobileNumber;
  String? gender;
  DateTime? dateOfBirth;
  String? permRegion;
  String? permProvince;
  String? permCity;
  String? permBarangay;
  String? idType;
  String? idFrontPhoto;
  String? idBackPhoto;
  String? selfiePhoto;

  Map<String, dynamic> toJson() {
    return {
      "user_id": userId,
      "first_name": firstName,
      "last_name": lastName,
      "email": email,
      "mobile": mobileNumber,
      "gender": gender,
      "dob": dateOfBirth?.toIso8601String(),
      "region": permRegion,
      "province": permProvince,
      "municipality": permCity,
      "barangay": permBarangay,
      "id_type": idType,
      "id_front_photo": idFrontPhoto,
      "id_back_photo": idBackPhoto,
      "selfie_photo": selfiePhoto,
    };
  }
}
