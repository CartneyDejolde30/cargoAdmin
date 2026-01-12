import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:async';
import 'call_service.dart';

class CallScreen extends StatefulWidget {
  final String callId;
  final String peerName;
  final String peerAvatar;
  final bool isIncoming;
  final CallService callService;

  const CallScreen({
    super.key,
    required this.callId,
    required this.peerName,
    required this.peerAvatar,
    required this.isIncoming,
    required this.callService,
  });

  @override
  State<CallScreen> createState() => _CallScreenState();
}

class _CallScreenState extends State<CallScreen> with TickerProviderStateMixin {
  bool _isMuted = false;
  bool _isSpeakerOn = false;
  CallState _callState = CallState.connecting;
  
  Timer? _callTimer;
  int _callDuration = 0;
  
  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    
    // Setup pulse animation for avatar
    _pulseController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    )..repeat(reverse: true);
    
    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.1).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
    
    // Listen to call state
    widget.callService.callState.listen((state) {
      setState(() {
        _callState = state;
      });
      
      if (state == CallState.connected) {
        _startCallTimer();
      } else if (state == CallState.ended || 
                 state == CallState.declined || 
                 state == CallState.error) {
        _endCall();
      }
    });
    
    // Auto-answer if incoming call was already accepted
    if (!widget.isIncoming) {
      setState(() {
        _callState = CallState.ringing;
      });
    }
  }

  void _startCallTimer() {
    _callTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        _callDuration++;
      });
    });
  }

  String _formatDuration(int seconds) {
    int minutes = seconds ~/ 60;
    int remainingSeconds = seconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${remainingSeconds.toString().padLeft(2, '0')}';
  }

  void _toggleMute() {
    setState(() {
      _isMuted = !_isMuted;
    });
    widget.callService.toggleMute(_isMuted);
  }

  void _toggleSpeaker() {
    setState(() {
      _isSpeakerOn = !_isSpeakerOn;
    });
    widget.callService.toggleSpeaker(_isSpeakerOn);
  }

  void _endCall() {
    _callTimer?.cancel();
    widget.callService.endCall();
    Navigator.of(context).pop();
  }

  void _answerCall() {
    widget.callService.answerCall(widget.callId);
  }

  void _declineCall() {
    widget.callService.declineCall(widget.callId);
    Navigator.of(context).pop();
  }

  @override
  void dispose() {
    _callTimer?.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        _endCall();
        return false;
      },
      child: Scaffold(
        backgroundColor: Colors.black,
        body: SafeArea(
          child: Stack(
            children: [
              // Background gradient
              Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black,
                      Colors.grey.shade900,
                    ],
                  ),
                ),
              ),
              
              // Main content
              Column(
                children: [
                  const SizedBox(height: 40),
                  
                  // Call status
                  Text(
                    _getCallStatusText(),
                    style: GoogleFonts.poppins(
                      color: Colors.white70,
                      fontSize: 16,
                    ),
                  ),
                  
                  const SizedBox(height: 8),
                  
                  // Call duration
                  if (_callState == CallState.connected)
                    Text(
                      _formatDuration(_callDuration),
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  
                  const Spacer(),
                  
                  // Avatar with pulse animation
                  ScaleTransition(
                    scale: _callState == CallState.ringing 
                        ? _pulseAnimation 
                        : AlwaysStoppedAnimation(1.0),
                    child: Container(
                      width: 140,
                      height: 140,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withOpacity(0.3),
                          width: 3,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.blue.withOpacity(0.3),
                            blurRadius: 30,
                            spreadRadius: 5,
                          ),
                        ],
                      ),
                      child: CircleAvatar(
                        radius: 70,
                        backgroundColor: Colors.grey.shade800,
                        backgroundImage: widget.peerAvatar.isNotEmpty
                            ? NetworkImage(widget.peerAvatar)
                            : null,
                        child: widget.peerAvatar.isEmpty
                            ? const Icon(
                                Icons.person,
                                size: 60,
                                color: Colors.white54,
                              )
                            : null,
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: 24),
                  
                  // Peer name
                  Text(
                    widget.peerName,
                    style: GoogleFonts.poppins(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.w600,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  
                  const Spacer(),
                  
                  // Control buttons
                  if (_callState == CallState.connected || 
                      _callState == CallState.ringing && !widget.isIncoming)
                    _buildCallControls(),
                  
                  // Incoming call buttons
                  if (widget.isIncoming && _callState == CallState.ringing)
                    _buildIncomingCallButtons(),
                  
                  const SizedBox(height: 40),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _getCallStatusText() {
    switch (_callState) {
      case CallState.connecting:
        return 'Connecting...';
      case CallState.ringing:
        return widget.isIncoming ? 'Incoming call' : 'Ringing...';
      case CallState.connected:
        return 'Connected';
      case CallState.declined:
        return 'Call declined';
      case CallState.ended:
        return 'Call ended';
      case CallState.error:
        return 'Connection error';
      default:
        return '';
    }
  }

  Widget _buildCallControls() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          // Mute button
          _buildControlButton(
            icon: _isMuted ? Icons.mic_off : Icons.mic,
            label: _isMuted ? 'Unmute' : 'Mute',
            onTap: _toggleMute,
            backgroundColor: _isMuted ? Colors.red : Colors.white24,
          ),
          
          // End call button
          _buildControlButton(
            icon: Icons.call_end,
            label: 'End',
            onTap: _endCall,
            backgroundColor: Colors.red,
            isLarge: true,
          ),
          
          // Speaker button
          _buildControlButton(
            icon: _isSpeakerOn ? Icons.volume_up : Icons.volume_down,
            label: _isSpeakerOn ? 'Speaker' : 'Earpiece',
            onTap: _toggleSpeaker,
            backgroundColor: _isSpeakerOn ? Colors.blue : Colors.white24,
          ),
        ],
      ),
    );
  }

  Widget _buildIncomingCallButtons() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 60),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          // Decline button
          Column(
            children: [
              GestureDetector(
                onTap: _declineCall,
                child: Container(
                  width: 70,
                  height: 70,
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.call_end,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Decline',
                style: GoogleFonts.poppins(
                  color: Colors.white70,
                  fontSize: 14,
                ),
              ),
            ],
          ),
          
          // Accept button
          Column(
            children: [
              GestureDetector(
                onTap: _answerCall,
                child: Container(
                  width: 70,
                  height: 70,
                  decoration: const BoxDecoration(
                    color: Colors.green,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.call,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Accept',
                style: GoogleFonts.poppins(
                  color: Colors.white70,
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildControlButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    required Color backgroundColor,
    bool isLarge = false,
  }) {
    return Column(
      children: [
        GestureDetector(
          onTap: onTap,
          child: Container(
            width: isLarge ? 70 : 60,
            height: isLarge ? 70 : 60,
            decoration: BoxDecoration(
              color: backgroundColor,
              shape: BoxShape.circle,
            ),
            child: Icon(
              icon,
              color: Colors.white,
              size: isLarge ? 32 : 28,
            ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: GoogleFonts.poppins(
            color: Colors.white70,
            fontSize: 12,
          ),
        ),
      ],
    );
  }
}