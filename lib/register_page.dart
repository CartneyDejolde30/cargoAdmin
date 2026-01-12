import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController = TextEditingController();
  final TextEditingController _fullNameController = TextEditingController();

  String? selectedMunicipality;
  String? selectedRole;

  Future<void> _register() async {
    if (_formKey.currentState!.validate()) {
      var url = Uri.parse("http://10.139.150.2/carGOAdmin/register.php"); 
      var response = await http.post(
        url,
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({
          "fullname": _fullNameController.text,
          "email": _emailController.text,
          "password": _passwordController.text,
          "municipality": selectedMunicipality,
          "role": selectedRole,
        }),
      );

      try {
        var data = jsonDecode(response.body);

        if (data["status"] == "success") {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Registration Successful")),
          );
          Navigator.pop(context);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data["message"] ?? "Registration failed")),
          );
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Server error. Check your PHP API.")),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(25.0),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Logo
                Row(
                  children: [
                    Image.asset('assets/cargo.png', width: 50, height: 50),
                    const SizedBox(width: 10),
                    const Text(
                      'CarGo',
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: Colors.black,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                const Text(
                  'Create an Account',
                  style: TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
                const SizedBox(height: 5),
                const Text(
                  'Join CarGo and start your journey today.',
                  style: TextStyle(fontSize: 16, color: Colors.black54),
                ),
                const SizedBox(height: 30),

                Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      // Full Name
                      TextFormField(
                        controller: _fullNameController,
                        decoration: const InputDecoration(
                          labelText: 'Full Name',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) =>
                            value!.isEmpty ? 'Please enter your full name' : null,
                      ),
                      const SizedBox(height: 15),

                      // Email
                      TextFormField(
                        controller: _emailController,
                        decoration: const InputDecoration(
                          labelText: 'Email',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) =>
                            value!.isEmpty ? 'Please enter your email' : null,
                      ),
                      const SizedBox(height: 15),

                      // Password
                      TextFormField(
                        controller: _passwordController,
                        obscureText: true,
                        decoration: const InputDecoration(
                          labelText: 'Password',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) =>
                            value!.isEmpty ? 'Please enter your password' : null,
                      ),
                      const SizedBox(height: 15),

                      // Confirm Password
                      TextFormField(
                        controller: _confirmPasswordController,
                        obscureText: true,
                        decoration: const InputDecoration(
                          labelText: 'Confirm Password',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) {
                          if (value!.isEmpty) return 'Please confirm password';
                          if (value != _passwordController.text) {
                            return 'Passwords do not match';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 15),

                      // Municipality Dropdown (Agusan del Sur)
                      DropdownButtonFormField<String>(
  initialValue: selectedMunicipality,
  hint: const Text('Select Municipality'),
  items: const [
    DropdownMenuItem(value: 'Bayugan', child: Text('Bayugan')),
    DropdownMenuItem(value: 'Bunawan', child: Text('Bunawan')),
    DropdownMenuItem(value: 'Esperanza', child: Text('Esperanza')),
    DropdownMenuItem(value: 'La Paz', child: Text('La Paz')),
    DropdownMenuItem(value: 'Loreto', child: Text('Loreto')),
    DropdownMenuItem(value: 'Prosperidad', child: Text('Prosperidad')),
    DropdownMenuItem(value: 'Rosario', child: Text('Rosario')),
    DropdownMenuItem(value: 'San Francisco', child: Text('San Francisco')),
    DropdownMenuItem(value: 'San Luis', child: Text('San Luis')),
    DropdownMenuItem(value: 'Santa Josefa', child: Text('Santa Josefa')),
    DropdownMenuItem(value: 'Sibagat', child: Text('Sibagat')),
    DropdownMenuItem(value: 'Talacogon', child: Text('Talacogon')),
    DropdownMenuItem(value: 'Trento', child: Text('Trento')),
    DropdownMenuItem(value: 'Veruela', child: Text('Veruela')),
  ],
  onChanged: (value) {
    setState(() => selectedMunicipality = value!);
  },
  decoration: InputDecoration(
    labelText: 'Municipality',
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
    ),
  ),
  validator: (value) =>
      value == null ? 'Please select your municipality' : null,
),
const SizedBox(height: 15),



                      // Role Dropdown
                      DropdownButtonFormField<String>(
                        initialValue: selectedRole,
                        hint: const Text('Select UserType'),
                        items: const [
                          DropdownMenuItem(value: 'Renter', child: Text('Renter')),
                          DropdownMenuItem(value: 'Owner', child: Text('Owner')),
                        ],
                        onChanged: (value) {
                          setState(() => selectedRole = value!);
                        },
                        decoration: InputDecoration(
                          labelText: 'UserType',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (value) =>
                            value == null ? 'Please select your role' : null,
                      ),
                      const SizedBox(height: 25),

                      // Register Button
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: ElevatedButton(
                          onPressed: _register,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.black,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(30),
                            ),
                          ),
                          child: const Text('Sign Up', style: TextStyle(fontSize: 18)),
                        ),
                      ),
                      const SizedBox(height: 25),

                      // Already have account?
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text('Already have an account? ',
                              style: TextStyle(color: Colors.black54)),
                          GestureDetector(
                            onTap: () => Navigator.pop(context),
                            child: const Text(
                              'Login',
                              style: TextStyle(
                                color: Colors.black,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}