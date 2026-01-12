import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import '../../Owner/mycar/api_constants.dart';
import './dashboard_stats.dart';

class DashboardService {
  /* ---------------- FETCH DASHBOARD STATS ---------------- */
  Future<DashboardStats> fetchDashboardStats(String ownerId) async {
    try {
      final url = Uri.parse("${ApiConstants.baseUrl}api/dashboard/dashboard_stats.php?owner_id=$ownerId");
      final response = await http.get(url).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          return DashboardStats.fromJson(data['stats']);
        } else {
          debugPrint("❌ Dashboard stats error: ${data['message']}");
          return DashboardStats.empty();
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching dashboard stats: $e");
    }

    return DashboardStats.empty();
  }

  /* ---------------- FETCH REVENUE TREND (For Chart) ---------------- */
  Future<List<Map<String, dynamic>>> fetchRevenueTrend(String ownerId, {String period = 'week'}) async {
    try {
      final url = Uri.parse("${ApiConstants.baseUrl}api/revenue_trend.php?owner_id=$ownerId&period=$period");
      final response = await http.get(url).timeout(ApiConstants.apiTimeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['trend'] is List) {
          return List<Map<String, dynamic>>.from(data['trend']);
        }
      }
    } catch (e) {
      debugPrint("❌ Error fetching revenue trend: $e");
    }

    return [];
  }
}