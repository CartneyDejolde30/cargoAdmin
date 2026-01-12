import 'dart:convert';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

class GoogleSignInService {
  // ✅ CRITICAL: Add your Web Client ID from Firebase Console
  // Get it from: Firebase Console → Project Settings → General → Your apps → Web app
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    // ⚠️ REPLACE THIS with your actual Web Client ID from google-services.json
    serverClientId: '647942447613-386insqu8jh5emdn1q1r2ugmd0kojfkr.apps.googleusercontent.com',
  );
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;

  // Your PHP backend URL - UPDATE THIS WITH YOUR ACTUAL IP
  static const String baseUrl = "http://10.139.150.2/carGOAdmin";

  /// Sign in with Google
  Future<Map<String, dynamic>?> signInWithGoogle() async {
    try {
      print('🔵 Starting Google Sign-In...');
      
      // Use signIn() method
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      
      if (googleUser == null) {
        print('⚪ User canceled sign-in');
        return null;
      }

      print('🟢 Google account selected: ${googleUser.email}');

      // Obtain auth details
      final GoogleSignInAuthentication googleAuth = await googleUser.authentication;

      // Access tokens directly (they are String? in v7.2.0)
      final credential = GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      print('🟡 Signing in to Firebase...');
      
      // Sign in to Firebase
      final UserCredential userCredential = await _auth.signInWithCredential(credential);
      final User? firebaseUser = userCredential.user;

      if (firebaseUser == null) {
        print('❌ Firebase user is null');
        return {'error': 'Firebase authentication failed'};
      }

      print('🟢 Firebase sign-in successful: ${firebaseUser.uid}');

      // Check if user exists in your MySQL database
      final existingUser = await _checkUserInDatabase(firebaseUser.email!);

      if (existingUser != null) {
        print('✅ Existing user found in database');
        // User exists - perform login
        return await _handleExistingUser(existingUser, firebaseUser);
      } else {
        print('🆕 New user - registration required');
        // New user - need to register
        return {
          'isNewUser': true,
          'email': firebaseUser.email,
          'fullName': firebaseUser.displayName ?? 'User',
          'photoUrl': firebaseUser.photoURL,
          'firebaseUid': firebaseUser.uid,
        };
      }
    } catch (e) {
      print('❌ Google Sign-In Error: $e');
      return {'error': e.toString()};
    }
  }

  /// Check if user exists in MySQL database
  Future<Map<String, dynamic>?> _checkUserInDatabase(String email) async {
    try {
      print('🔍 Checking user in database: $email');
      
      final response = await http.post(
        Uri.parse("$baseUrl/check_google_user.php"),
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({"email": email}),
      ).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw Exception('Connection timeout - check your server');
        },
      );

      print('📡 Database check response: ${response.statusCode}');
      print('📄 Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['exists'] == true) {
          print('✅ User exists in database');
          return data['user'];
        } else {
          print('ℹ️ User does not exist in database');
        }
      } else {
        print('⚠️ Unexpected status code: ${response.statusCode}');
      }
      return null;
    } catch (e) {
      print('❌ Database Check Error: $e');
      return null;
    }
  }

  /// Handle existing user login
  Future<Map<String, dynamic>> _handleExistingUser(
    Map<String, dynamic> userData,
    User firebaseUser,
  ) async {
    try {
      print('💾 Saving user data to SharedPreferences...');
      
      // Save to SharedPreferences
      SharedPreferences prefs = await SharedPreferences.getInstance();
      await prefs.setString("user_id", userData["id"].toString());
      await prefs.setString("fullname", userData["fullname"]);
      await prefs.setString("email", userData["email"]);
      await prefs.setString("role", userData["role"]);
      await prefs.setString("phone", userData["phone"] ?? "");
      await prefs.setString("address", userData["address"] ?? "");
      await prefs.setString("municipality", userData["municipality"] ?? "");
      await prefs.setString("profile_image", userData["profile_image"] ?? firebaseUser.photoURL ?? "");

      print('🔥 Updating Firestore...');
      
      // Update/Create Firestore user
      await _createOrUpdateFirestoreUser(userData, firebaseUser);

      print('✅ User login successful');

      return {
        'success': true,
        'isNewUser': false,
        'user': userData,
      };
    } catch (e) {
      print('❌ Error handling existing user: $e');
      return {
        'success': false,
        'error': e.toString(),
      };
    }
  }

  /// Register new Google user in MySQL
  Future<Map<String, dynamic>?> registerGoogleUser({
    required String email,
    required String fullName,
    required String role,
    required String municipality,
    String? photoUrl,
    String? firebaseUid,
  }) async {
    try {
      print('📝 Registering new Google user...');
      print('Email: $email, Name: $fullName, Role: $role, Municipality: $municipality');
      
      final response = await http.post(
        Uri.parse("$baseUrl/google_register.php"),
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({
          "email": email,
          "fullname": fullName,
          "role": role,
          "municipality": municipality,
          "profile_image": photoUrl ?? "",
          "firebase_uid": firebaseUid ?? "",
          "phone": "",
          "address": "",
        }),
      ).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw Exception('Registration timeout - check your server');
        },
      );

      print('📡 Registration response: ${response.statusCode}');
      print('📄 Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['status'] == 'success') {
          print('✅ Registration successful');
          
          // Save to SharedPreferences
          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setString("user_id", data["user"]["id"].toString());
          await prefs.setString("fullname", data["user"]["fullname"]);
          await prefs.setString("email", data["user"]["email"]);
          await prefs.setString("role", data["user"]["role"]);
          await prefs.setString("municipality", data["user"]["municipality"] ?? "");
          await prefs.setString("profile_image", data["user"]["profile_image"] ?? "");
          await prefs.setString("phone", data["user"]["phone"] ?? "");
          await prefs.setString("address", data["user"]["address"] ?? "");

          print('🔥 Creating Firestore user...');
          
          // Create Firestore user
          final User? firebaseUser = _auth.currentUser;
          if (firebaseUser != null) {
            await _createOrUpdateFirestoreUser(data["user"], firebaseUser);
          }

          return {
            'success': true,
            'user': data["user"],
          };
        } else {
          print('⚠️ Registration failed: ${data["message"]}');
          return {
            'success': false,
            'error': data["message"] ?? 'Registration failed',
          };
        }
      } else {
        print('❌ Server error: ${response.statusCode}');
        return {
          'success': false,
          'error': 'Server returned ${response.statusCode}',
        };
      }
    } catch (e) {
      print('❌ Registration Error: $e');
      return {
        'success': false,
        'error': e.toString(),
      };
    }
  }

  /// Create or update Firestore user document
  Future<void> _createOrUpdateFirestoreUser(
    Map<String, dynamic> userData,
    User firebaseUser,
  ) async {
    try {
      final userRef = _firestore.collection("users").doc(userData["id"].toString());
      final token = await FirebaseMessaging.instance.getToken();

      print('📱 FCM Token: $token');

      final docSnapshot = await userRef.get();

      if (!docSnapshot.exists) {
        print('🆕 Creating new Firestore document...');
        
        // Create new document
        await userRef.set({
          "uid": userData["id"].toString(),
          "name": userData["fullname"],
          "avatar": userData["profile_image"] ?? firebaseUser.photoURL ?? "",
          "email": userData["email"],
          "role": userData["role"],
          "online": true,
          "createdAt": FieldValue.serverTimestamp(),
          "fcm": token,
        });
        print("🔥 Firestore user CREATED");
      } else {
        print('🔄 Updating existing Firestore document...');
        
        // Update existing document
        await userRef.update({
          "online": true,
          "avatar": userData["profile_image"] ?? firebaseUser.photoURL ?? "",
          "name": userData["fullname"],
          "fcm": token,
        });
        print("✅ Firestore user UPDATED");
      }
    } catch (e) {
      print("❌ Firestore Error: $e");
      rethrow;
    }
  }

  /// Sign out from all services
  Future<void> signOut() async {
    try {
      print('👋 Signing out...');
      
      // Update Firestore status to offline
      final User? currentUser = _auth.currentUser;
      if (currentUser != null) {
        SharedPreferences prefs = await SharedPreferences.getInstance();
        String? userId = prefs.getString("user_id");
        
        if (userId != null) {
          await _firestore.collection("users").doc(userId).update({
            "online": false,
          });
        }
      }
      
      await _googleSignIn.signOut();
      await _auth.signOut();
      
      SharedPreferences prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      
      print('✅ Sign out successful');
    } catch (e) {
      print('❌ Sign out error: $e');
    }
  }

  /// Check if user is currently signed in
  Future<bool> isSignedIn() async {
    return await _googleSignIn.isSignedIn();
  }

  /// Get current Firebase user
  User? getCurrentUser() {
    return _auth.currentUser;
  }
}