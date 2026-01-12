import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class EditProfileScreen extends StatefulWidget {
  final String api;
  const EditProfileScreen({super.key, required this.api});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen>
    with SingleTickerProviderStateMixin {

  File? imageFile;
  Uint8List? webImage;

  late AnimationController controller;
  late Animation<double> fadeAnimation;
  late Animation<double> scaleAnimation;

  final nameController = TextEditingController();
  final phoneController = TextEditingController();
  final addressController = TextEditingController();

  String storedProfile = "";
  bool saving = false;
  bool hasChanges = false;

  @override
  void initState() {
    super.initState();
    animate();
    loadData();

    nameController.addListener(_detectChanges);
    phoneController.addListener(_detectChanges);
    addressController.addListener(_detectChanges);
  }

  void animate() {
    controller = AnimationController(
        duration: const Duration(milliseconds: 500), vsync: this);

    fadeAnimation = CurvedAnimation(parent: controller, curve: Curves.easeOut);
    scaleAnimation = Tween<double>(begin: 0.8, end: 1.0)
        .animate(CurvedAnimation(parent: controller, curve: Curves.easeOutBack));

    controller.forward();
  }

  Future<void> loadData() async {
    final prefs = await SharedPreferences.getInstance();
    nameController.text = prefs.getString("fullname") ?? "";
    phoneController.text = prefs.getString("phone") ?? "";
    addressController.text = prefs.getString("address") ?? "";
    storedProfile = prefs.getString("profile_image") ?? "";
    setState(() {});
  }

  void _detectChanges() {
    setState(() => hasChanges = true);
  }

  Future pickImage() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery);

    if (picked == null) return;

    if (kIsWeb) {
      webImage = await picked.readAsBytes();
    } else {
      imageFile = File(picked.path);
    }
    setState(() => hasChanges = true);
  }

  ImageProvider? avatarImage() {
    if (imageFile != null) return FileImage(imageFile!);
    if (webImage != null) return MemoryImage(webImage!);
    if (storedProfile.isNotEmpty) return NetworkImage(storedProfile);
    return null;
  }

  Future<void> save() async {
    if (!hasChanges) return;

    setState(() => saving = true);

    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString("user_id") ?? "";

    var req = http.MultipartRequest("POST", Uri.parse(widget.api));
    req.fields["user_id"] = userId;
    req.fields["fullname"] = nameController.text.trim();
    req.fields["phone"] = phoneController.text.trim();
    req.fields["address"] = addressController.text.trim();

    if (imageFile != null) {
      req.files.add(await http.MultipartFile.fromPath("profile_image", imageFile!.path));
    }

    if (kIsWeb && webImage != null) {
      req.files.add(http.MultipartFile.fromBytes(
        "profile_image",
        webImage!,
        filename: "profile$userId.png",
      ));
    }

    final res = await req.send();
    final json = jsonDecode(await res.stream.bytesToString());

    if (json["success"] == true) {
      await prefs.setString("fullname", json["user"]["fullname"]);
      await prefs.setString("phone", json["user"]["phone"]);
      await prefs.setString("address", json["user"]["address"]);
      String baseURL = "http://10.139.150.2/carGOAdmin/uploads/";

        await prefs.setString(
          "profile_image",
          (json["user"]["profile_image"] != null && json["user"]["profile_image"] != "")
              ? baseURL + json["user"]["profile_image"]
              : ""
        );


      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Profile Updated 🎉")),
      );

      Navigator.pop(context, true);
    }

    setState(() => saving = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,

      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        title: const Text("Edit Profile",
            style: TextStyle(fontWeight: FontWeight.bold)),
        centerTitle: true,
      ),

      body: FadeTransition(
        opacity: fadeAnimation,
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 10),
          child: Column(
            children: [
              const SizedBox(height: 20),

              ScaleTransition(
                scale: scaleAnimation,
                child: Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    CircleAvatar(
                      radius: 75,
                      backgroundColor: Colors.grey.shade300,
                      backgroundImage: avatarImage(),
                      child: avatarImage() == null
                          ? const Icon(Icons.person, size: 70, color: Colors.black45)
                          : null,
                    ),
                    GestureDetector(
                      onTap: pickImage,
                      child: CircleAvatar(
                        radius: 22,
                        backgroundColor: Colors.black,
                        child: const Icon(Icons.camera_alt,
                            color: Colors.white, size: 19),
                      ),
                    )
                  ],
                ),
              ),

              const SizedBox(height: 40),

              _buildField("Full Name", nameController),
              _buildField("Phone Number", phoneController,
                  inputType: TextInputType.phone),
              _buildField("Address", addressController),

              const SizedBox(height: 100),
            ],
          ),
        ),
      ),

      bottomNavigationBar: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        padding: const EdgeInsets.all(20),
        child: ElevatedButton(
          onPressed: (!saving && hasChanges) ? save : null,
          style: ElevatedButton.styleFrom(
            backgroundColor:
                (!saving && hasChanges) ? Colors.black : Colors.grey,
            minimumSize: const Size(double.infinity, 55),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12)),
          ),
          child: saving
              ? const CircularProgressIndicator(color: Colors.white)
              : const Text("Save Changes",
                  style: TextStyle(fontWeight: FontWeight.w600)),
        ),
      ),
    );
  }

  Widget _buildField(String label, TextEditingController controller,
      {TextInputType inputType = TextInputType.text}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: TextField(
        controller: controller,
        keyboardType: inputType,
        decoration: InputDecoration(
          labelText: label,
          floatingLabelBehavior: FloatingLabelBehavior.auto,
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14)),
        ),
      ),
    );
  }
}
