import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import './api_constants.dart';

class CarService {
  /* ---------------- FETCH CARS ---------------- */
  Future<List<Map<String, dynamic>>> fetchCars(int ownerId) async {
    try {
      final response = await http
          .post(
            Uri.parse(ApiConstants.carsApi),
            body: {
              "action": "fetch",
              "owner_id": ownerId.toString(),
            },
          )
          .timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);

        // PHP returns a LIST of cars
        if (decoded is List) {
          return List<Map<String, dynamic>>.from(decoded);
        } else {
          debugPrint("⚠ Unexpected response format: $decoded");
        }
      } else {
        debugPrint("❌ HTTP Error: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("❌ Error fetching cars: $e");
    }

    return [];
  }

  /* ---------------- DELETE CAR ---------------- */
  Future<bool> deleteCar(int carId) async {
    try {
      final response = await http
          .post(
            Uri.parse(ApiConstants.carsApi),
            body: {
              "action": "delete",
              "id": carId.toString(),
            },
          )
          .timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        return decoded is Map && decoded["success"] == true;
      }
    } catch (e) {
      debugPrint("❌ Delete error: $e");
    }

    return false;
  }
}
