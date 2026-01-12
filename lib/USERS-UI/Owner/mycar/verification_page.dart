import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import './api_constants.dart';

class VerificationService {
  /* ---------------- CHECK VERIFICATION STATUS ---------------- */
  Future<Map<String, bool>> checkVerification() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('user_id');

      if (userId == null || userId.isEmpty) {
        return {'isVerified': false, 'canAddCar': false};
      }

      final url = Uri.parse("${ApiConstants.checkVerificationApi}?user_id=$userId");
      final response = await http.get(url).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        return {
          'isVerified': result['is_verified'] == true,
          'canAddCar': result['can_add_car'] == true,
        };
      }
    } catch (e) {
      debugPrint("❌ Error checking verification: $e");
    }

    return {'isVerified': false, 'canAddCar': false};
  }
}