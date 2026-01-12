import 'package:animate_do/animate_do.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../Renter/chats/chat_detail_screen.dart';

class MessagePage extends StatefulWidget {
  const MessagePage({super.key});

  @override
  State<MessagePage> createState() => _MessagePageState();
}

class _MessagePageState extends State<MessagePage> {
  String currentUserId = "";
  bool loading = true;
  String searchQuery = "";

  @override
  void initState() {
    super.initState();
    loadUser();
  }

  Future<void> loadUser() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    currentUserId = prefs.getString("user_id") ?? "";
    setState(() => loading = false);
  }

  Stream<QuerySnapshot<Map<String, dynamic>>> getChats() {
    if (currentUserId.isEmpty) return const Stream.empty();

    return FirebaseFirestore.instance
        .collection("chats")
        .where("members", arrayContains: currentUserId)
        .orderBy("lastTimestamp", descending: true)
        .snapshots();
  }

  String extractOtherUser(List members) {
    return members.first == currentUserId ? members.last : members.first;
  }

  bool isValidUrl(String url) {
    return url.startsWith("http");
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,

      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 1,
        iconTheme: const IconThemeData(color: Colors.black),
        title: Text(
          "Messages",
          style: GoogleFonts.poppins(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: Colors.black,
          ),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 14),
            child: Image.asset("assets/cargo.png", width: 34),
          ),
        ],
      ),

      body: loading
          ? const Center(child: CircularProgressIndicator(color: Colors.black))
          : Column(
              children: [

                Padding(
                  padding: const EdgeInsets.all(12),
                  child: TextField(
                    onChanged: (v) => setState(() => searchQuery = v),
                    style: const TextStyle(color: Colors.black),
                    decoration: InputDecoration(
                      hintText: "Search messages...",
                      hintStyle: TextStyle(color: Colors.grey.shade600),
                      filled: true,
                      fillColor: Colors.grey.shade200,
                      prefixIcon: const Icon(Icons.search, color: Colors.black87),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(28),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),

                Expanded(
                  child: StreamBuilder(
                    stream: getChats(),
                    builder: (context, snapshot) {
                      if (!snapshot.hasData) {
                        return const Center(
                            child: CircularProgressIndicator(color: Colors.black));
                      }

                      final chats = snapshot.data!.docs;

                      if (chats.isEmpty) {
                        return Center(
                          child: Text(
                            "No messages yet",
                            style: TextStyle(color: Colors.grey.shade700, fontSize: 16),
                          ),
                        );
                      }

                      return Column(
                        children: [

                          SizedBox(
                            height: 95,
                            child: ListView.builder(
                              scrollDirection: Axis.horizontal,
                              padding: const EdgeInsets.symmetric(horizontal: 12),
                              itemCount: chats.length,
                              itemBuilder: (_, index) {
                                final chatDoc = chats[index];
                                final userId = extractOtherUser(chatDoc["members"]);

                                return FutureBuilder<DocumentSnapshot>(
                                  future: FirebaseFirestore.instance
                                      .collection("users")
                                      .doc(userId)
                                      .get(),
                                  builder: (context, snapshot) {
                                    if (!snapshot.hasData) return const SizedBox();

                                    final user = snapshot.data!;
                                    final avatar = user["avatar"] ?? "";
                                    final name = user["name"] ?? "User";

                                    return Padding(
                                      padding: const EdgeInsets.only(right: 14),
                                      child: FadeInUp(
                                        duration: Duration(milliseconds: 300 + (index * 60)),
                                        child: Column(
                                          children: [
                                            CircleAvatar(
                                              radius: 30,
                                              backgroundColor: Colors.grey.shade300,
                                              backgroundImage: isValidUrl(avatar)
                                                  ? CachedNetworkImageProvider(avatar)
                                                  : null,
                                              child: !isValidUrl(avatar)
                                                  ? const Icon(Icons.person, size: 28)
                                                  : null,
                                            ),
                                            const SizedBox(height: 5),
                                            Text(
                                              name.split(" ").first,
                                              style: const TextStyle(
                                                  color: Colors.black, fontSize: 12),
                                            )
                                          ],
                                        ),
                                      ),
                                    );
                                  },
                                );
                              },
                            ),
                          ),

                          Expanded(
                            child: ListView.builder(
                              itemCount: chats.length,
                              padding: const EdgeInsets.only(top: 6),
                              itemBuilder: (_, index) {
                                final chatDoc = chats[index];
                                final userId = extractOtherUser(chatDoc["members"]);

                                return FutureBuilder<DocumentSnapshot>(
                                  future: FirebaseFirestore.instance
                                      .collection("users")
                                      .doc(userId)
                                      .get(),
                                  builder: (context, snap) {
                                    if (!snap.hasData) return const SizedBox();

                                    final user = snap.data!;
                                    final avatar = user["avatar"] ?? "";
                                    final name = user["name"] ?? "Unknown";
                                    final lastMessage = chatDoc["lastMessage"] ?? "";

                                    return SlideInUp(
                                      duration: Duration(milliseconds: 350 + index * 80),
                                      child: ListTile(
                                        leading: CircleAvatar(
                                          radius: 26,
                                          backgroundColor: Colors.grey.shade300,
                                          backgroundImage: isValidUrl(avatar)
                                              ? CachedNetworkImageProvider(avatar)
                                              : null,
                                          child: !isValidUrl(avatar)
                                              ? const Icon(Icons.person)
                                              : null,
                                        ),
                                        title: Text(
                                          name,
                                          style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.w600),
                                        ),
                                        subtitle: Text(
                                          lastMessage == "typing..."
                                              ? "Typing..."
                                              : lastMessage,
                                          maxLines: 1,
                                          style:
                                              TextStyle(color: Colors.grey.shade600),
                                        ),
                                        onTap: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (_) => ChatDetailScreen(
                                                chatId: chatDoc.id,
                                                peerId: userId,
                                                peerName: name,
                                                peerAvatar: avatar,
                                              ),
                                            ),
                                          );
                                        },
                                      ),
                                    );
                                  },
                                );
                              },
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ],
            ),
    );
  }
}
