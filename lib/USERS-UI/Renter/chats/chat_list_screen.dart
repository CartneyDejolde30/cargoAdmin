import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:animate_do/animate_do.dart';

import '../widgets/bottom_nav_bar.dart';
import 'package:flutter_application_1/USERS-UI/Renter/chats/chat_detail_screen.dart';

class ChatListScreen extends StatefulWidget {
  const ChatListScreen({super.key});

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> {
  final TextEditingController _searchController = TextEditingController();
  int _currentNavIndex = 3;

  String currentUserId = "";
  List<QueryDocumentSnapshot> chats = [];
  List<QueryDocumentSnapshot> filteredChats = [];

  @override
  void initState() {
    super.initState();
    loadUser();
  }

  Future<void> loadUser() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    currentUserId = prefs.getString("user_id") ?? "";

    if (currentUserId.isEmpty) return;

    listenChats();
    setState(() {});
  }

  void listenChats() {
    FirebaseFirestore.instance
        .collection("chats")
        .where("members", arrayContains: currentUserId)
        .orderBy("lastTimestamp", descending: true)
        .snapshots()
        .listen((snapshot) {
      setState(() {
        chats = snapshot.docs;
        filteredChats = chats;
      });
    });
  }

  void searchChats(String text) {
    setState(() {
      filteredChats = text.isEmpty
          ? chats
          : chats.where((chat) {
              final List members = chat["members"];
              final peerId =
                  members.first == currentUserId ? members.last : members.first;
              return peerId.toLowerCase().contains(text.toLowerCase());
            }).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      bottomNavigationBar: BottomNavBar(
        currentIndex: _currentNavIndex,
        onTap: (index) => setState(() => _currentNavIndex = index),
      ),

      body: SafeArea(
        child: Column(
          children: [
            _appBar(),
            _searchBar(),
            _sectionTitle(),
            _storyBubbles(),
            Expanded(child: _buildList()),
          ],
        ),
      ),
    );
  }

  // ---------------- UI ----------------

  Widget _appBar() {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
    decoration: const BoxDecoration(
      color: Colors.white,
      boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 6)],
    ),
    child: Row(
      children: [
        // Removed back button
        Expanded(
          child: Text(
            "Messages",
            style: GoogleFonts.poppins(fontSize: 22, fontWeight: FontWeight.bold),
          ),
        ),
        Image.asset("assets/cargo.png", width: 32),
      ],
    ),
  );
}


  Widget _searchBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
      child: TextField(
        controller: _searchController,
        onChanged: searchChats,
        decoration: InputDecoration(
          prefixIcon: const Icon(Icons.search),
          hintText: "Search...",
          fillColor: Colors.grey.shade200,
          filled: true,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(30), borderSide: BorderSide.none),
        ),
      ),
    );
  }

  Widget _sectionTitle() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Text(
          "Recent",
          style: GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }

  Widget _storyBubbles() {
    if (chats.isEmpty) return const SizedBox();

    return SizedBox(
      height: 95,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.only(left: 20),
        itemCount: chats.length,
        itemBuilder: (_, i) => _storyItem(chats[i], i),
      ),
    );
  }

  Widget _storyItem(QueryDocumentSnapshot chat, int index) {
    List members = chat["members"];
    String peerId = members.first == currentUserId ? members.last : members.first;

    return FutureBuilder<DocumentSnapshot>(
      future: FirebaseFirestore.instance.collection("users").doc(peerId).get(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) return const SizedBox();

        final user = snapshot.data!;
        final name = user.data().toString().contains("name") ? user["name"] : "User";
        final img = user.data().toString().contains("avatar") ? user["avatar"] : "";

        return FadeInUp(
          duration: Duration(milliseconds: 200 + (index * 60)),
          child: Padding(
            padding: const EdgeInsets.only(right: 14),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: Colors.grey.shade300,
                  backgroundImage: img.isNotEmpty ? NetworkImage(img) : null,
                  child: img.isEmpty ? const Icon(Icons.person, size: 28, color: Colors.black54) : null,
                ),
                const SizedBox(height: 5),
                Text(name.split(" ").first, style: const TextStyle(fontSize: 11)),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildList() {
    if (filteredChats.isEmpty) {
      return Center(child: Text("No chats found", style: GoogleFonts.poppins(color: Colors.grey)));
    }

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 18),
      itemCount: filteredChats.length,
      itemBuilder: (_, i) => _chatTile(filteredChats[i], i),
    );
  }

  Widget _chatTile(QueryDocumentSnapshot chat, int index) {
    List members = chat["members"];
    String peerId = members.first == currentUserId ? members.last : members.first;

    bool isTyping = chat.data().toString().contains("${peerId}_typing")
        ? chat["${peerId}_typing"]
        : false;

    bool isUnread = chat["lastSender"] != currentUserId && chat["seen"] == false;

    return FutureBuilder<DocumentSnapshot>(
      future: FirebaseFirestore.instance.collection("users").doc(peerId).get(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) return const SizedBox(height: 65);

        final user = snapshot.data!;
        final name = user.data().toString().contains("name") ? user["name"] : "User";
        final img = user.data().toString().contains("avatar") ? user["avatar"] : "";

        return FadeInUp(
          duration: Duration(milliseconds: 200 + index * 80),
          child: GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                PageRouteBuilder(
                  pageBuilder: (_, __, ___) =>
                      ChatDetailScreen(peerId: peerId, peerName: name, peerAvatar: img, chatId: chat.id),
                  transitionsBuilder: (_, animation, __, child) =>
                      FadeTransition(opacity: animation, child: child),
                ),
              );
            },

            child: Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade200,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 26,
                    backgroundColor: Colors.grey.shade300,
                    backgroundImage: img.isNotEmpty ? NetworkImage(img) : null,
                    child: img.isEmpty ? const Icon(Icons.person, color: Colors.black54) : null,
                  ),

                  const SizedBox(width: 14),

                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(name, style: GoogleFonts.poppins(fontWeight: FontWeight.w600, fontSize: 15)),
                        Text(
                          isTyping ? "Typing..." : chat["lastMessage"],
                          style: TextStyle(color: isUnread ? Colors.black : Colors.grey.shade600),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),

                  Text(
                    chat["lastTimestamp"] != null
                        ? chat["lastTimestamp"].toDate().toString().substring(11, 16)
                        : "",
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
