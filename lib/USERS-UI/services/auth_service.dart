import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';

class AuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _db = FirebaseFirestore.instance;

  Future<User?> signInAnonymously(String name) async {
    UserCredential result = await _auth.signInAnonymously();
    User user = result.user!;

    await _db.collection("users").doc(user.uid).set({
      "name": name,
      "avatar": "https://ui-avatars.com/api/?name=$name",
      "isOnline": true,
      "lastMessage": "",
      "lastMessageTime": DateTime.now(),
    });

    return user;
  }

  Stream<List<Map<String, dynamic>>> getUsers() {
    return _db.collection("users").snapshots().map((snap) =>
        snap.docs.map((doc) => doc.data()).toList());
  }
}
