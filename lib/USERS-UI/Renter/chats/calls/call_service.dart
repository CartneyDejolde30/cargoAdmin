import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';
import 'dart:async';

class CallService {
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  
  RTCPeerConnection? _peerConnection;
  MediaStream? _localStream;
  MediaStream? _remoteStream;
  
  final _remoteStreamController = StreamController<MediaStream>.broadcast();
  final _callStateController = StreamController<CallState>.broadcast();
  
  Stream<MediaStream> get remoteStream => _remoteStreamController.stream;
  Stream<CallState> get callState => _callStateController.stream;
  
  String? currentCallId;
  
  // WebRTC Configuration
  final Map<String, dynamic> _configuration = {
    'iceServers': [
      {'urls': 'stun:stun.l.google.com:19302'},
      {'urls': 'stun:stun1.l.google.com:19302'},
    ]
  };
  
  final Map<String, dynamic> _constraints = {
    'mandatory': {},
    'optional': [
      {'DtlsSrtpKeyAgreement': true},
    ],
  };
  
  // Initialize local media stream (audio only)
  Future<MediaStream> _createLocalStream() async {
    final Map<String, dynamic> mediaConstraints = {
      'audio': true,
      'video': false, // Audio call only
    };
    
    MediaStream stream = await navigator.mediaDevices.getUserMedia(mediaConstraints);
    return stream;
  }
  
  // Create peer connection
  Future<RTCPeerConnection> _createPeerConnection() async {
    RTCPeerConnection pc = await createPeerConnection(_configuration, _constraints);
    
    pc.onIceCandidate = (RTCIceCandidate candidate) {
      if (currentCallId != null) {
        _firestore
            .collection('calls')
            .doc(currentCallId)
            .collection('candidates')
            .add(candidate.toMap());
      }
    };
    
    pc.onAddStream = (MediaStream stream) {
      _remoteStream = stream;
      _remoteStreamController.add(stream);
    };
    
    pc.onIceConnectionState = (RTCIceConnectionState state) {
      print('ICE Connection State: $state');
      if (state == RTCIceConnectionState.RTCIceConnectionStateDisconnected ||
          state == RTCIceConnectionState.RTCIceConnectionStateFailed ||
          state == RTCIceConnectionState.RTCIceConnectionStateClosed) {
        _callStateController.add(CallState.ended);
      }
    };
    
    return pc;
  }
  
  // Initiate a call
  Future<String> startCall({
    required String callerId,
    required String callerName,
    required String receiverId,
    required String receiverName,
  }) async {
    try {
      _callStateController.add(CallState.connecting);
      
      // Create local stream
      _localStream = await _createLocalStream();
      
      // Create peer connection
      _peerConnection = await _createPeerConnection();
      _peerConnection!.addStream(_localStream!);
      
      // Create offer
      RTCSessionDescription offer = await _peerConnection!.createOffer({
        'offerToReceiveAudio': true,
        'offerToReceiveVideo': false,
      });
      await _peerConnection!.setLocalDescription(offer);
      
      // Create call document in Firestore
      DocumentReference callDoc = await _firestore.collection('calls').add({
        'callerId': callerId,
        'callerName': callerName,
        'receiverId': receiverId,
        'receiverName': receiverName,
        'offer': offer.toMap(),
        'status': 'ringing',
        'type': 'audio',
        'timestamp': FieldValue.serverTimestamp(),
      });
      
      currentCallId = callDoc.id;
      
      // Listen for answer
      callDoc.snapshots().listen((snapshot) {
        if (snapshot.exists) {
          Map<String, dynamic>? data = snapshot.data() as Map<String, dynamic>?;
          
          if (data != null && data['answer'] != null && data['answer']['sdp'] != null) {
            RTCSessionDescription answer = RTCSessionDescription(
              data['answer']['sdp'],
              data['answer']['type'],
            );
            _peerConnection!.setRemoteDescription(answer);
            _callStateController.add(CallState.connected);
          }
          
          if (data != null && data['status'] == 'declined') {
            endCall();
            _callStateController.add(CallState.declined);
          }
        }
      });
      
      // Listen for ICE candidates
      _listenToIceCandidates(callDoc.id, 'receiver');
      
      return callDoc.id;
    } catch (e) {
      print('Error starting call: $e');
      _callStateController.add(CallState.error);
      rethrow;
    }
  }
  
  // Answer incoming call
  Future<void> answerCall(String callId) async {
    try {
      _callStateController.add(CallState.connecting);
      currentCallId = callId;
      
      // Get call data
      DocumentSnapshot callDoc = await _firestore.collection('calls').doc(callId).get();
      Map<String, dynamic> callData = callDoc.data() as Map<String, dynamic>;
      
      // Create local stream
      _localStream = await _createLocalStream();
      
      // Create peer connection
      _peerConnection = await _createPeerConnection();
      _peerConnection!.addStream(_localStream!);
      
      // Set remote description (offer)
      RTCSessionDescription offer = RTCSessionDescription(
        callData['offer']['sdp'],
        callData['offer']['type'],
      );
      await _peerConnection!.setRemoteDescription(offer);
      
      // Create answer
      RTCSessionDescription answer = await _peerConnection!.createAnswer({
        'offerToReceiveAudio': true,
        'offerToReceiveVideo': false,
      });
      await _peerConnection!.setLocalDescription(answer);
      
      // Update call document with answer
      await _firestore.collection('calls').doc(callId).update({
        'answer': answer.toMap(),
        'status': 'active',
      });
      
      _callStateController.add(CallState.connected);
      
      // Listen for ICE candidates
      _listenToIceCandidates(callId, 'caller');
    } catch (e) {
      print('Error answering call: $e');
      _callStateController.add(CallState.error);
      rethrow;
    }
  }
  
  // Decline incoming call
  Future<void> declineCall(String callId) async {
    await _firestore.collection('calls').doc(callId).update({
      'status': 'declined',
    });
    _callStateController.add(CallState.declined);
  }
  
  // End call
  Future<void> endCall() async {
    try {
      if (currentCallId != null) {
        await _firestore.collection('calls').doc(currentCallId).update({
          'status': 'ended',
          'endTime': FieldValue.serverTimestamp(),
        });
      }
      
      // Close peer connection
      await _peerConnection?.close();
      _peerConnection = null;
      
      // Stop local stream
      _localStream?.getTracks().forEach((track) {
        track.stop();
      });
      _localStream = null;
      
      // Stop remote stream
      _remoteStream?.getTracks().forEach((track) {
        track.stop();
      });
      _remoteStream = null;
      
      currentCallId = null;
      _callStateController.add(CallState.ended);
    } catch (e) {
      print('Error ending call: $e');
    }
  }
  
  // Listen to incoming ICE candidates
  void _listenToIceCandidates(String callId, String source) {
    _firestore
        .collection('calls')
        .doc(callId)
        .collection('candidates')
        .where('source', isEqualTo: source)
        .snapshots()
        .listen((snapshot) {
      for (var change in snapshot.docChanges) {
        if (change.type == DocumentChangeType.added) {
          Map<String, dynamic> data = change.doc.data() as Map<String, dynamic>;
          RTCIceCandidate candidate = RTCIceCandidate(
            data['candidate'],
            data['sdpMid'],
            data['sdpMLineIndex'],
          );
          _peerConnection?.addCandidate(candidate);
        }
      }
    });
  }
  
  // Toggle mute
  void toggleMute(bool mute) {
    if (_localStream != null) {
      _localStream!.getAudioTracks().forEach((track) {
        track.enabled = !mute;
      });
    }
  }
  
  // Toggle speaker
  void toggleSpeaker(bool speaker) {
    if (_localStream != null) {
      Helper.setSpeakerphoneOn(speaker);
    }
  }
  
  // Listen for incoming calls
  Stream<DocumentSnapshot> listenForIncomingCalls(String userId) {
    return _firestore
        .collection('calls')
        .where('receiverId', isEqualTo: userId)
        .where('status', isEqualTo: 'ringing')
        .orderBy('timestamp', descending: true)
        .limit(1)
        .snapshots()
        .map((snapshot) => snapshot.docs.first);
  }
  
  // Dispose
  void dispose() {
    endCall();
    _remoteStreamController.close();
    _callStateController.close();
  }
}

enum CallState {
  idle,
  connecting,
  ringing,
  connected,
  declined,
  ended,
  error,
}