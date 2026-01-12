import 'dart:convert';
import 'package:http/http.dart' as http;

import '../models/user_verification.dart';

class VerificationService {
  static const String baseUrl =
      "http://10.139.150.2/carGOAdmin/api/submit_verification.php";

  static Future<Map<String, dynamic>> submitVerification(
      UserVerification user) async {
    try {
      var uri = Uri.parse(baseUrl);
      var request = http.MultipartRequest('POST', uri);

      // ---- FORMAT DOB ----
      String formattedDOB = "";
      if (user.dateOfBirth != null) {
        formattedDOB =
            "${user.dateOfBirth!.year}-${user.dateOfBirth!.month.toString().padLeft(2, '0')}-${user.dateOfBirth!.day.toString().padLeft(2, '0')}";
      }

      // ---- TEXT FIELDS ----
      request.fields.addAll({
        "user_id": user.userId.toString(),
        "first_name": user.firstName ?? "",
        "last_name": user.lastName ?? "",
        "suffix": user.suffix ?? "",
        "nationality": user.nationality ?? "",
        "gender": user.gender ?? "",
        "email": user.email ?? "",
        "mobile_number": user.mobileNumber ?? "",
        "id_type": user.idType ?? "",
        "date_of_birth": formattedDOB,

        "permRegion": user.permRegion ?? "",
        "permProvince": user.permProvince ?? "",
        "permCity": user.permCity ?? "",
        "permBarangay": user.permBarangay ?? "",
        "permZipCode": user.permZipCode ?? "",
        "permAddressLine": user.permAddressLine ?? "",

        "presRegion": user.presRegion ?? "",
        "presProvince": user.presProvince ?? "",
        "presCity": user.presCity ?? "",
        "presBarangay": user.presBarangay ?? "",
        "presZipCode": user.presZipCode ?? "",
        "presAddressLine": user.presAddressLine ?? "",
      });

      // ---- FILE UPLOADS (MOBILE) ----
      if (user.idFrontFile != null) {
        request.files.add(await http.MultipartFile.fromPath(
          'id_front',
          user.idFrontFile!.path,
        ));
      }

      if (user.idBackFile != null) {
        request.files.add(await http.MultipartFile.fromPath(
          'id_back',
          user.idBackFile!.path,
        ));
      }

      if (user.selfieFile != null) {
        request.files.add(await http.MultipartFile.fromPath(
          'selfie',
          user.selfieFile!.path,
        ));
      }

      // ---- BASE64 UPLOADS (WEB) ----
      if (user.idFrontFile == null && user.idFrontPhoto != null) {
        request.fields['id_front_base64'] = user.idFrontPhoto!;
      }
      if (user.idBackFile == null && user.idBackPhoto != null) {
        request.fields['id_back_base64'] = user.idBackPhoto!;
      }
      if (user.selfieFile == null && user.selfiePhoto != null) {
        request.fields['selfie_base64'] = user.selfiePhoto!;
      }

      // ---- SEND ----
      var response = await request.send();
      var result = await http.Response.fromStream(response);

      print("📩 SERVER RESPONSE: ${result.body}");

      return json.decode(result.body);

    } catch (e) {
      print("❌ ERROR in submitVerification: $e");
      return {
        "success": false,
        "message": "Something went wrong. Try again."
      };
    }
  }
}
