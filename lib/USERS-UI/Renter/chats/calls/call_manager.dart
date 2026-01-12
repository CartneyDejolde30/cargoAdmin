import 'package:flutter/material.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'call_service.dart';
import 'incoming_call_overlay.dart';
import 'call_screen.dart';
import 'dart:async';

class CallManager {
  static final CallManager _instance = CallManager._internal();
  factory CallManager() => _instance;
  CallManager._internal();

  final CallService callService = CallService();
  StreamSubscription<QuerySnapshot>? _callSubscription;
  bool _isListening = false;

  // Initialize call listener
  Future<void> initialize(BuildContext context) async {
    if (_isListening) return;

    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('user_id');

    if (userId == null || userId.isEmpty) return;

    _isListening = true;

    // Listen for incoming calls
    _callSubscription = FirebaseFirestore.instance
        .collection('calls')
        .where('receiverId', isEqualTo: userId)
        .where('status', isEqualTo: 'ringing')
        .snapshots()
        .listen((snapshot) {
      if (snapshot.docs.isNotEmpty) {
        final callDoc = snapshot.docs.first;
        final callData = callDoc.data();

        // Show incoming call overlay
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (ctx) => IncomingCallOverlay(
            callId: callDoc.id,
            callerName: callData['callerName'] ?? 'Unknown',
            callerAvatar: callData['callerAvatar'] ?? '',
            callService: callService,
          ),
        );
      }
    });
  }

  // Make an outgoing call
  Future<void> makeCall({
    required BuildContext context,
    required String receiverId,
    required String receiverName,
    required String receiverAvatar,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final callerId = prefs.getString('user_id') ?? '';
      final callerName = prefs.getString('fullname') ?? 'Unknown';

      final callId = await callService.startCall(
        callerId: callerId,
        callerName: callerName,
        receiverId: receiverId,
        receiverName: receiverName,
      );

      // Navigate to call screen
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => CallScreen(
            callId: callId,
            peerName: receiverName,
            peerAvatar: receiverAvatar,
            isIncoming: false,
            callService: callService,
          ),
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to start call: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  // Dispose resources
  void dispose() {
    _callSubscription?.cancel();
    callService.dispose();
    _isListening = false;
  }
}