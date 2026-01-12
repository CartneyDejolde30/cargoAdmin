import 'dart:io';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:firebase_storage/firebase_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ChatDetailScreen extends StatefulWidget {
  final String chatId;
  final String peerId;
  final String peerName;
  final String peerAvatar;

  const ChatDetailScreen({
    super.key,
    required this.chatId,
    required this.peerId,
    required this.peerName,
    required this.peerAvatar,
  });

  @override
  State<ChatDetailScreen> createState() => _ChatDetailScreenState();
}

class _ChatDetailScreenState extends State<ChatDetailScreen> {
  final TextEditingController _messageController = TextEditingController();
  String currentUserId = "";
  bool isTyping = false;
  File? selectedImage;

  CollectionReference get messageRef =>
      FirebaseFirestore.instance.collection("chats").doc(widget.chatId).collection("messages");

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  // ---------------------- USER SETUP ----------------------

  Future<void> _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    currentUserId = prefs.getString("user_id") ?? "";
    await _createChatIfNeeded();
    setState(() {});
    _markMessagesSeen();
  }

  Future<void> _createChatIfNeeded() async {
    final chatDoc = FirebaseFirestore.instance.collection("chats").doc(widget.chatId);
    final exists = await chatDoc.get();

    if (!exists.exists) {
      await chatDoc.set({
        "members": [currentUserId, widget.peerId],
        "lastMessage": "",
        "lastSender": "",
        "peerAvatar": widget.peerAvatar,
        "peerName": widget.peerName,
        "createdAt": FieldValue.serverTimestamp(),
        "seen": false,
      });
    }
  }

  // ---------------------- MESSAGE HANDLING ----------------------

  Future<void> _markMessagesSeen() async {
    if (currentUserId.isEmpty) return;

    final unreadMessages = await messageRef
        .where("receiverId", isEqualTo: currentUserId)
        .where("seen", isEqualTo: false)
        .get();

    for (final doc in unreadMessages.docs) {
      doc.reference.update({"seen": true});
    }

    FirebaseFirestore.instance.collection("chats").doc(widget.chatId).update({"seen": true});
  }

  Future<void> _sendMessage({String? imageUrl}) async {
    final text = _messageController.text.trim();
    if (text.isEmpty && imageUrl == null) return;

    final now = FieldValue.serverTimestamp();

    await messageRef.add({
      "text": text,
      "senderId": currentUserId,
      "receiverId": widget.peerId,
      "timestamp": now,
      "seen": false,
      "image": imageUrl ?? "",
    });

    await FirebaseFirestore.instance.collection("chats").doc(widget.chatId).update({
      "lastMessage": imageUrl != null ? "📷 Photo" : text,
      "lastSender": currentUserId,
      "lastTimestamp": now,
      "seen": false,
    });

    _messageController.clear();
    selectedImage = null;
    setState(() {});
    _setTyping(false);
  }

  // ---------------------- IMAGE PICKING ----------------------

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final img = await picker.pickImage(source: ImageSource.gallery);

    if (img != null) {
      selectedImage = File(img.path);
      setState(() {});
    }
  }

  Future<void> _uploadImage() async {
    if (selectedImage == null) return;

    final fileName = "${DateTime.now().millisecondsSinceEpoch}.jpg";
    final ref = FirebaseStorage.instance.ref("chat_images/$fileName");

    await ref.putFile(selectedImage!);
    final url = await ref.getDownloadURL();

    await _sendMessage(imageUrl: url);
  }

  // ---------------------- STREAMS ----------------------

  Stream<QuerySnapshot> _getMessages() {
    return messageRef.orderBy("timestamp", descending: true).snapshots();
  }

  void _setTyping(bool value) {
    if (value == isTyping) return;

    isTyping = value;
    FirebaseFirestore.instance.collection("chats").doc(widget.chatId).update({
      "${currentUserId}_typing": value,
    });

    setState(() {});
  }

  // ---------------------- UI ----------------------

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: _buildAppBar(),
      body: Column(
        children: [
          Expanded(child: _buildMessagesList()),
          if (selectedImage != null) _previewSelectedImage(),
          _buildInputBar(),
        ],
      ),
    );
  }

  AppBar _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 1,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back, color: Colors.black),
        onPressed: () => Navigator.pop(context),
      ),
      title: GestureDetector(
        onTap: () {
          Navigator.push(
            context,
            PageRouteBuilder(
              opaque: true,
              pageBuilder: (_, __, ___) => FullImageView(widget.peerAvatar),
              transitionsBuilder: (_, anim, __, child) => FadeTransition(opacity: anim, child: child),
            ),
          );
        },
        child: Row(
          children: [
            CircleAvatar(backgroundImage: NetworkImage(widget.peerAvatar)),
            const SizedBox(width: 10),
            Text(widget.peerName, style: GoogleFonts.poppins(fontSize: 16)),
          ],
        ),
      ),
    );
  }

  Widget _previewSelectedImage() {
    return Container(
      margin: const EdgeInsets.all(10),
      child: Stack(
        alignment: Alignment.topRight,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(selectedImage!, height: 140),
          ),
          GestureDetector(
            onTap: () => setState(() => selectedImage = null),
            child: const CircleAvatar(
              radius: 14,
              backgroundColor: Colors.black,
              child: Icon(Icons.close, color: Colors.white, size: 16),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildMessagesList() {
    return StreamBuilder<QuerySnapshot>(
      stream: _getMessages(),
      builder: (_, snapshot) {
        if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());

        final messages = snapshot.data!.docs;

        return ListView.builder(
          reverse: true,
          padding: const EdgeInsets.all(16),
          itemCount: messages.length,
          itemBuilder: (_, i) => _buildMessageBubble(messages[i]),
        );
      },
    );
  }

  Widget _buildMessageBubble(QueryDocumentSnapshot msg) {
    final isMe = msg["senderId"] == currentUserId;

    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Column(
        crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          if (msg["image"] != "")
            GestureDetector(
              onTap: () => Navigator.push(
                context,
                PageRouteBuilder(
                  opaque: true,
                  pageBuilder: (_, __, ___) => FullImageView(msg["image"]),
                  transitionsBuilder: (_, anim, __, child) => FadeTransition(opacity: anim, child: child),
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(msg["image"], width: 180),
              ),
            ),

          Container(
            margin: const EdgeInsets.symmetric(vertical: 6),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isMe ? Colors.black : Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              msg["text"],
              style: TextStyle(color: isMe ? Colors.white : Colors.black, fontSize: 15),
            ),
          ),

          if (isMe)
            Text(
              msg["seen"] ? "Seen ✔" : "Delivered",
              style: const TextStyle(fontSize: 10, color: Colors.grey),
            ),
        ],
      ),
    );
  }

  Widget _buildInputBar() {
    return Container(
      padding: const EdgeInsets.all(16),
      color: Colors.white,
      child: Row(
        children: [
          IconButton(icon: const Icon(Icons.image), onPressed: _pickImage),
          Expanded(
            child: TextField(
              controller: _messageController,
              onChanged: (v) => _setTyping(v.isNotEmpty),
              decoration: InputDecoration(
                hintText: "Message...",
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () => selectedImage != null ? _uploadImage() : _sendMessage(),
            child: CircleAvatar(
              backgroundColor: Colors.black,
              child: Icon(selectedImage != null ? Icons.upload : Icons.send, color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }
}

class FullImageView extends StatelessWidget {
  final String url;

  const FullImageView(this.url, {super.key});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.pop(context),
      child: Scaffold(
        backgroundColor: Colors.black87,
        body: Center(
          child: Hero(
            tag: url,
            child: Image.network(url),
          ),
        ),
      ),
    );
  }
}
