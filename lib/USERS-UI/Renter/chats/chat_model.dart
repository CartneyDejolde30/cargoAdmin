import 'package:cloud_firestore/cloud_firestore.dart';

class ChatModel {
  final String chatId;
  final String name;
  final String avatarUrl;
  final String lastMessage;
  final DateTime? timestamp;
  final String receiverId;
  final bool seen;

  ChatModel({
    required this.chatId,
    required this.name,
    required this.avatarUrl,
    required this.lastMessage,
    required this.timestamp,
    required this.receiverId,
    required this.seen,
  });

  factory ChatModel.fromFirestore(String id, Map<String, dynamic> data, String currentUserId) {
    Timestamp? ts = data['lastTimestamp'];

    // Determine peerId from "members" if not stored directly
    String peer = data["peerId"] ?? "";

    if (peer.isEmpty && data["members"] is List) {
      List members = data["members"];
      peer = members.first == currentUserId ? members.last : members.first;
    }

    return ChatModel(
      chatId: id,
      name: data['peerName'] ?? '',
      avatarUrl: data['peerAvatar'] ?? '',
      lastMessage: data['lastMessage'] ?? '',
      timestamp: ts != null ? ts.toDate() : null,
      receiverId: peer,
      seen: data['seen'] ?? false,
    );
  }
}
