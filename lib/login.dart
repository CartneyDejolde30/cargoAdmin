import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'register_page.dart';
import 'package:firebase_database/firebase_database.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter_application_1/USERS-UI/Renter/renters.dart';
import 'package:flutter_application_1/USERS-UI/Owner/owner_home_screen.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_application_1/Google/google_sign_in_service.dart';
import 'package:flutter_application_1/Facebook/facebook_sign_in_service.dart';


class CarGoApp extends StatelessWidget {
  const CarGoApp({super.key});

void setUserStatus(String userId, bool online) {
  final ref = FirebaseDatabase.instance.ref("status/$userId");

  ref.set({
    "isOnline": online,
    "lastSeen": ServerValue.timestamp,
  });

  ref.onDisconnect().set({
    "isOnline": false,
    "lastSeen": ServerValue.timestamp,
  });
}

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'CarGo Login',
      theme: ThemeData(
        brightness: Brightness.light,
        primaryColor: Colors.black,
        scaffoldBackgroundColor: Colors.white,
      ),
      home: const LoginPage(),
    );
  }
}




class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final GoogleSignInService _googleSignInService = GoogleSignInService();
  final FacebookSignInService _facebookSignInService = FacebookSignInService();
  
  bool _obscurePassword = true;
  bool _rememberMe = false;
  bool _isGoogleSigningIn = false;
  bool _isFacebookSigningIn = false;

  @override
void initState() {
  super.initState();
  _loadSavedCredentials();
  _setupNotifications();
}

Future<void> _setupNotifications() async {
  await FirebaseMessaging.instance.requestPermission(
    alert: true,
    badge: true,
    sound: true,
  );
}


  void _loadSavedCredentials() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? email = prefs.getString('email');
    String? password = prefs.getString('password');
    bool remember = prefs.getBool('remember') ?? false;

    if (remember && email != null && password != null) {
      setState(() {
        _emailController.text = email;
        _passwordController.text = password;
        _rememberMe = true;
      });
    }
  }

  void _saveCredentials() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    if (_rememberMe) {
      await prefs.setString('email', _emailController.text);
      await prefs.setString('password', _passwordController.text);
      await prefs.setBool('remember', true);
    } else {
      await prefs.remove('email');
      await prefs.remove('password');
      await prefs.setBool('remember', false);
    }
  }

 Future<void> _createFirestoreUser(Map<String, dynamic> data) async {
  final userRef = FirebaseFirestore.instance.collection("users").doc(data["id"].toString());

  if (!(await userRef.get()).exists) {
    
    // GET TOKEN HERE
    final token = await FirebaseMessaging.instance.getToken();

    await userRef.set({
      "uid": data["id"].toString(),
      "fullname": data["fullname"],
      "email": data["email"],
      "profile_image": data["profile_image"] ?? "",
      "role": data["role"],
      "online": true,
      "created_at": FieldValue.serverTimestamp(),
      "vehicles": [],
      "rating": 0,
      "status": "active",
      "fcm": token,   // <-- Save token for new user also
    });
  }
}


  void _login() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill in all fields.')),
      );
      return;
    }

    _saveCredentials();

    final url = Uri.parse("http://10.139.150.2/carGOAdmin/login.php");

    print("Sending JSON -> email: $email, password: $password");

    try {
      final response = await http.post(
        url,
        headers: {"Content-Type": "application/json; charset=UTF-8"},
        body: jsonEncode({
          "email": email,
          "password": password,
        }),
      );

      print("Response status: ${response.statusCode}");
      print("Response body: ${response.body}");

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        if (data["status"] == "success") {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data["message"])),
          );

          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setString("user_id", data["id"].toString());  
          await prefs.setString("fullname", data["fullname"]);
          await prefs.setString("email", data["email"]);
          await prefs.setString("role", data["role"]);
          await prefs.setString("phone", data["phone"] ?? "");
          await prefs.setString("address", data["address"] ?? "");
          await prefs.setString("profile_image", data["profile_image"] ?? "");

          ///  FIX: Ensure user exists inside Firestore in correct format
              final userRef = FirebaseFirestore.instance.collection("users").doc(data["id"].toString());

              if (!(await userRef.get()).exists) {
                await userRef.set({
                  "uid": data["id"].toString(),
                  "name": data["fullname"],               
                  "avatar": data["profile_image"] ?? "",  
                  "email": data["email"],
                  "role": data["role"],
                  "online": true,
                  "createdAt": FieldValue.serverTimestamp(),
                });
                print("🔥 Firestore user CREATED");
              } else {
                print("✔ Firestore user already exists → updating status");
                        await userRef.update({
                          "online": true,
                          "avatar": data["profile_image"] ?? "",
                          "name": data["fullname"],
                        });

                        // 🔥 Save FCM Token for Push Notifications
                        try {
                          final token = await FirebaseMessaging.instance.getToken();
                          await userRef.update({"fcm": token});
                          print("📩 FCM Token Updated: $token");
                        } catch (e) {
                          print("❌ Failed to save FCM Token: $e");
                        }

                                      }

          String role = data["role"];
          if (role == "Renter") {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const HomeScreen()),
            );
          } else if (role == "Owner") {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => OwnerHomeScreen()),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Unknown user role.')),
            );
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data["message"] ?? "Login failed.")),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Server error: ${response.statusCode}")),
        );
      }
    } catch (e) {
      print("Error: $e");
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Error connecting to server.')),
      );
    }
  }

  // 🆕 GOOGLE SIGN-IN HANDLER
  Future<void> _handleGoogleSignIn() async {
    setState(() => _isGoogleSigningIn = true);

    try {
      final result = await _googleSignInService.signInWithGoogle();

      if (result == null) {
        // User canceled
        setState(() => _isGoogleSigningIn = false);
        return;
      }

      if (result.containsKey('error')) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Google Sign-In failed: ${result['error']}')),
        );
        setState(() => _isGoogleSigningIn = false);
        return;
      }

      if (result['isNewUser'] == true) {
        // New user - show role selection dialog
        setState(() => _isGoogleSigningIn = false);
        _showRoleSelectionDialog(result);
      } else {
        // Existing user - navigate to home
        final userData = result['user'];
        _navigateToHome(userData['role']);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sign-in error: $e')),
      );
    } finally {
      setState(() => _isGoogleSigningIn = false);
    }
  }

  //  SHOW ROLE SELECTION DIALOG (Google)
  void _showRoleSelectionDialog(Map<String, dynamic> googleData) {
    String? selectedRole;
    String? selectedMunicipality;

    final municipalities = [
      'Bayugan', 'Bunawan', 'Esperanza', 'La Paz', 'Loreto', 
      'Prosperidad', 'Rosario', 'San Francisco', 'San Luis', 
      'Santa Josefa', 'Sibagat', 'Talacogon', 'Trento', 'Veruela'
    ];

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Complete Your Profile'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('Welcome ${googleData['fullName']}!'),
                    const SizedBox(height: 20),
                    
                    // Role selection
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'I am a',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'Renter', child: Text('Renter')),
                        DropdownMenuItem(value: 'Owner', child: Text('Owner')),
                      ],
                      onChanged: (value) {
                        setDialogState(() => selectedRole = value);
                      },
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Municipality selection
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Municipality',
                        border: OutlineInputBorder(),
                      ),
                      items: municipalities.map((municipality) {
                        return DropdownMenuItem(
                          value: municipality,
                          child: Text(municipality),
                        );
                      }).toList(),
                      onChanged: (value) {
                        setDialogState(() => selectedMunicipality = value);
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.pop(context);
                    _googleSignInService.signOut();
                  },
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: (selectedRole != null && selectedMunicipality != null)
                      ? () async {
                          Navigator.pop(context);
                          await _completeGoogleRegistration(
                            googleData,
                            selectedRole!,
                            selectedMunicipality!,
                          );
                        }
                      : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                  ),
                  child: const Text('Continue'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  //  COMPLETE GOOGLE REGISTRATION
  Future<void> _completeGoogleRegistration(
    Map<String, dynamic> googleData,
    String role,
    String municipality,
  ) async {
    setState(() => _isGoogleSigningIn = true);

    final result = await _googleSignInService.registerGoogleUser(
      email: googleData['email'],
      fullName: googleData['fullName'],
      role: role,
      municipality: municipality,
      photoUrl: googleData['photoUrl'],
      firebaseUid: googleData['firebaseUid'],
    );

    setState(() => _isGoogleSigningIn = false);

    if (result != null && result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Account created successfully!')),
      );
      _navigateToHome(role);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Registration failed. Please try again.')),
      );
    }
  }

  //  FACEBOOK SIGN-IN HANDLER
  Future<void> _handleFacebookSignIn() async {
    setState(() => _isFacebookSigningIn = true);

    try {
      final result = await _facebookSignInService.signInWithFacebook();

      if (result == null) {
        // User canceled
        setState(() => _isFacebookSigningIn = false);
        return;
      }

      if (result.containsKey('error')) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Facebook Sign-In failed: ${result['error']}')),
        );
        setState(() => _isFacebookSigningIn = false);
        return;
      }

      if (result['isNewUser'] == true) {
        // New user - show role selection dialog
        setState(() => _isFacebookSigningIn = false);
        _showFacebookRoleSelectionDialog(result);
      } else {
        // Existing user - navigate to home
        final userData = result['user'];
        _navigateToHome(userData['role']);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sign-in error: $e')),
      );
    } finally {
      setState(() => _isFacebookSigningIn = false);
    }
  }

  //  SHOW ROLE SELECTION DIALOG (Facebook)
  void _showFacebookRoleSelectionDialog(Map<String, dynamic> facebookData) {
    String? selectedRole;
    String? selectedMunicipality;

    final municipalities = [
      'Bayugan', 'Bunawan', 'Esperanza', 'La Paz', 'Loreto', 
      'Prosperidad', 'Rosario', 'San Francisco', 'San Luis', 
      'Santa Josefa', 'Sibagat', 'Talacogon', 'Trento', 'Veruela'
    ];

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Complete Your Profile'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('Welcome ${facebookData['fullName']}!'),
                    const SizedBox(height: 20),
                    
                    // Role selection
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'I am a',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'Renter', child: Text('Renter')),
                        DropdownMenuItem(value: 'Owner', child: Text('Owner')),
                      ],
                      onChanged: (value) {
                        setDialogState(() => selectedRole = value);
                      },
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Municipality selection
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Municipality',
                        border: OutlineInputBorder(),
                      ),
                      items: municipalities.map((municipality) {
                        return DropdownMenuItem(
                          value: municipality,
                          child: Text(municipality),
                        );
                      }).toList(),
                      onChanged: (value) {
                        setDialogState(() => selectedMunicipality = value);
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.pop(context);
                    _facebookSignInService.signOut();
                  },
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: (selectedRole != null && selectedMunicipality != null)
                      ? () async {
                          Navigator.pop(context);
                          await _completeFacebookRegistration(
                            facebookData,
                            selectedRole!,
                            selectedMunicipality!,
                          );
                        }
                      : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                  ),
                  child: const Text('Continue'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  // 🆕 COMPLETE FACEBOOK REGISTRATION
  Future<void> _completeFacebookRegistration(
    Map<String, dynamic> facebookData,
    String role,
    String municipality,
  ) async {
    setState(() => _isFacebookSigningIn = true);

    final result = await _facebookSignInService.registerFacebookUser(
      email: facebookData['email'],
      fullName: facebookData['fullName'],
      role: role,
      municipality: municipality,
      photoUrl: facebookData['photoUrl'],
      firebaseUid: facebookData['firebaseUid'],
      facebookId: facebookData['facebookId'],
    );

    setState(() => _isFacebookSigningIn = false);

    if (result != null && result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Account created successfully!')),
      );
      _navigateToHome(role);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Registration failed. Please try again.')),
      );
    }
  }

  //  NAVIGATE TO HOME BASED ON ROLE
  void _navigateToHome(String role) {
    if (role == "Renter") {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const HomeScreen()),
      );
    } else if (role == "Owner") {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => OwnerHomeScreen()),
      );
    }
  }

  void _showForgotPasswordDialog() {
    TextEditingController emailController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Forgot Password"),
        content: TextField(
          controller: emailController,
          decoration: const InputDecoration(
            labelText: "Enter your email",
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                    content: Text(
                        'Password reset link sent to ${emailController.text}')),
              );
            },
            child: const Text("Send"),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Logo and Brand Name
              Row(
                children: [
                  Image.asset(
                    'assets/cargo.png',
                    width: 40,
                    height: 40,
                  ),
                  const SizedBox(width: 10),
                  const Text(
                    'CarGo',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 40),

              // Welcome Text
              const Text(
                'Welcome Back',
                style: TextStyle(
                  fontSize: 28,
                  color: Colors.black,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Ready to hit the road.',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.black54,
                ),
              ),
              const SizedBox(height: 30),

              // Email/Phone Input
              TextField(
                controller: _emailController,
                style: const TextStyle(color: Colors.black),
                decoration: InputDecoration(
                  hintText: 'Email/Phone Number',
                  hintStyle: const TextStyle(color: Colors.black38, fontSize: 14),
                  filled: true,
                  fillColor: Colors.grey[100],
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Password Input
              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                style: const TextStyle(color: Colors.black),
                decoration: InputDecoration(
                  hintText: 'Password',
                  hintStyle: const TextStyle(color: Colors.black38, fontSize: 14),
                  filled: true,
                  fillColor: Colors.grey[100],
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscurePassword ? Icons.visibility_off : Icons.visibility,
                      color: Colors.black38,
                      size: 20,
                    ),
                    onPressed: () {
                      setState(() {
                        _obscurePassword = !_obscurePassword;
                      });
                    },
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Remember Me and Forgot Password
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      SizedBox(
                        width: 20,
                        height: 20,
                        child: Checkbox(
                          value: _rememberMe,
                          onChanged: (value) {
                            setState(() => _rememberMe = value!);
                          },
                          activeColor: Colors.black,
                          checkColor: Colors.white,
                          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Text(
                        'Remember Me',
                        style: TextStyle(color: Colors.black87, fontSize: 13),
                      ),
                    ],
                  ),
                  TextButton(
                    onPressed: _showForgotPasswordDialog,
                    style: TextButton.styleFrom(
                      padding: EdgeInsets.zero,
                      minimumSize: const Size(0, 0),
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    child: const Text(
                      'Forgot Password',
                      style: TextStyle(color: Colors.black87, fontSize: 13),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Login Button
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton(
                  onPressed: _login,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.black,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text(
                    'Login',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // OR Divider
              Row(
                children: const [
                  Expanded(child: Divider(color: Colors.black26, thickness: 1)),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      'Or',
                      style: TextStyle(color: Colors.black38, fontSize: 13),
                    ),
                  ),
                  Expanded(child: Divider(color: Colors.black26, thickness: 1)),
                ],
              ),
              const SizedBox(height: 24),

              // Facebook Login Button (UPDATED)
              SizedBox(
                width: double.infinity,
                height: 52,
                child: OutlinedButton(
                  onPressed: _isFacebookSigningIn ? null : _handleFacebookSignIn,
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: Colors.black26, width: 1),
                    foregroundColor: Colors.black,
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _isFacebookSigningIn
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          mainAxisSize: MainAxisSize.min,
                          children: const [
                            Icon(Icons.facebook, color: Color(0xFF1877F2), size: 24),
                            SizedBox(width: 12),
                            Flexible(
                              child: Text(
                                'Continue with Facebook',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                ),
              ),
              const SizedBox(height: 12),

              // Google Login Button
              SizedBox(
                width: double.infinity,
                height: 52,
                child: OutlinedButton(
                  onPressed: _isGoogleSigningIn ? null : _handleGoogleSignIn,
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: Colors.black26, width: 1),
                    foregroundColor: Colors.black,
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _isGoogleSigningIn
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Image.asset(
                              'assets/search.png',
                              width: 20,
                              height: 20,
                            ),
                            const SizedBox(width: 12),
                            const Flexible(
                              child: Text(
                                'Continue with Google',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                ),
              ),
              const SizedBox(height: 24),

              // Sign Up Link at bottom
              Center(
                child: Wrap(
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    const Text(
                      "Don't have an account? ",
                      style: TextStyle(color: Colors.black54, fontSize: 13),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (context) => const RegisterPage()),
                        );
                      },
                      style: TextButton.styleFrom(
                        padding: EdgeInsets.zero,
                        minimumSize: const Size(0, 0),
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: const Text(
                        'Sign Up',
                        style: TextStyle(
                          color: Colors.black,
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}