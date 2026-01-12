import 'dart:io';

class UserVerification {
  // --- User Reference ---
  int? userId;
  String verificationStatus;

  // --- Personal Information ---
  String? firstName;
  String? lastName;
  String? suffix;
  String? nationality;
  String? gender;
  DateTime? dateOfBirth;

  // --- Permanent Address ---
  String? permRegion;
  String? permProvince;
  String? permCity;
  String? permBarangay;
  String? permZipCode;
  String? permAddressLine;

  // --- Present Address ---
  bool sameAsPermanent;
  String? presRegion;
  String? presProvince;
  String? presCity;
  String? presBarangay;
  String? presZipCode;
  String? presAddressLine;

  // --- Contact Info ---
  String? email;
  String? mobileNumber;

  // --- Verification Documents ---
  String? idType;

  /// Filenames saved in DB (optional — used after approval)
  String? idFrontPhoto;
  String? idBackPhoto;
  String? selfiePhoto;

  /// ✔ ACTUAL FILES FOR UPLOAD (MOBILE ONLY)
  File? idFrontFile;
  File? idBackFile;
  File? selfieFile;

  UserVerification({
    this.userId,
    this.verificationStatus = "pending",

    this.firstName,
    this.lastName,
    this.suffix,
    this.nationality,
    this.gender,
    this.dateOfBirth,

    this.permRegion,
    this.permProvince,
    this.permCity,
    this.permBarangay,
    this.permZipCode,
    this.permAddressLine,

    this.sameAsPermanent = false,
    this.presRegion,
    this.presProvince,
    this.presCity,
    this.presBarangay,
    this.presZipCode,
    this.presAddressLine,

    this.email,
    this.mobileNumber,

    this.idType,

    this.idFrontPhoto,
    this.idBackPhoto,
    this.selfiePhoto,

    this.idFrontFile,
    this.idBackFile,
    this.selfieFile,
  });

  Map<String, dynamic> toJson() {
  return {
    "user_id": userId,
    "verification_status": verificationStatus,

    "first_name": firstName,
    "last_name": lastName,
    "suffix": suffix,
    "nationality": nationality,
    "gender": gender,
    "date_of_birth": dateOfBirth?.toIso8601String(),

    "permRegion": permRegion,
    "permProvince": permProvince,
    "permCity": permCity,
    "permBarangay": permBarangay,
    "permZipCode": permZipCode,
    "permAddressLine": permAddressLine,

    "sameAsPermanent": sameAsPermanent,
    "presRegion": presRegion,
    "presProvince": presProvince,
    "presCity": presCity,
    "presBarangay": presBarangay,
    "presZipCode": presZipCode,
    "presAddressLine": presAddressLine,

    "email": email,
    "mobile_number": mobileNumber,
    "mobileNumber": mobileNumber, // backup
    "id_type": idType,

    "idFrontPhoto": idFrontPhoto,
    "idBackPhoto": idBackPhoto,
    "selfiePhoto": selfiePhoto,
  };
}

}
