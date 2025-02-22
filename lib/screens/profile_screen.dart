import 'package:flutter/material.dart';
import 'package:gas/screens/user_type_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:gas/core/constants.dart';



class ProfileScreen extends StatefulWidget {
  final Map<String, dynamic> userData;

  const ProfileScreen({super.key, required this.userData});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _emailController;
  late TextEditingController _phoneController;
  late TextEditingController _addressController;
  bool _isEditing = false;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.userData['name']);
    _emailController = TextEditingController(text: widget.userData['email']);
    _phoneController = TextEditingController(text: widget.userData['phone']);
    _addressController = TextEditingController(text: widget.userData['address']);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  Future<void> _updateProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.updateProfileEndpoint),
        body: {
          'nic': widget.userData['nic'],
          'name': _nameController.text,
          'email': _emailController.text,
          'phone': _phoneController.text,
          'address': _addressController.text,
        },
      );

      final result = json.decode(response.body);
      if (result['success'] == true) {
        if (!mounted) return;
        
        // Update the userData in parent screens
        widget.userData.addAll(result['user']);
        
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Profile updated successfully'),
            backgroundColor: Colors.green,
          ),
        );
        setState(() {
          _isEditing = false;
          widget.userData['name'] = _nameController.text;
          widget.userData['email'] = _emailController.text;
          widget.userData['phone'] = _phoneController.text;
          widget.userData['address'] = _addressController.text;
        });
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Update failed'),
            backgroundColor: AppColors.primaryBlue,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: AppColors.primaryBlue,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Widget _buildTextField({
    required String label,
    required TextEditingController controller,
    required IconData icon,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: TextFormField(
        controller: controller,
        enabled: _isEditing,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon, color: AppColors.primaryBlue),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: AppColors.primaryBlue.withOpacity(0.3)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: AppColors.primaryBlue),
          ),
          disabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: AppColors.textSecondary.withOpacity(0.3)),
          ),
        ),
        validator: validator,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Profile',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                AppColors.primaryBlue,
                AppColors.primaryBlue.withOpacity(0.8),
              ],
            ),
          ),
        ),
        actions: [
          PopupMenuButton<String>(
            icon: const CircleAvatar(
              backgroundColor: Colors.white,
              child: Icon(Icons.person, color: AppColors.primaryBlue),
            ),
            onSelected: (value) {
              if (value == 'logout') {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (context) => const UserTypeScreen()),
                  (route) => false,
                );
              }
            },
            itemBuilder: (BuildContext context) => [
              PopupMenuItem<String>(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, color: AppColors.primaryBlue),
                    const SizedBox(width: 8),
                    const Text('Logout'),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(width: 16),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppColors.primaryBlue.withOpacity(0.05),
              Colors.white,
              AppColors.primaryBlue.withOpacity(0.05),
            ],
          ),
        ),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Form(
            key: _formKey,
            child: Column(
              children: [
                CircleAvatar(
                  radius: 50,
                  backgroundColor: AppColors.primaryBlue.withOpacity(0.1),
                  child: Icon(
                    Icons.person,
                    size: 50,
                    color: AppColors.primaryBlue,
                  ),
                ),
                const SizedBox(height: 24),
                Card(
                  elevation: 4,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: [
                        _buildTextField(
                          label: 'Full Name',
                          controller: _nameController,
                          icon: Icons.person_outline,
                          validator: (value) =>
                              value?.isEmpty ?? true ? 'Please enter your name' : null,
                        ),
                        _buildTextField(
                          label: 'Email',
                          controller: _emailController,
                          icon: Icons.email_outlined,
                          keyboardType: TextInputType.emailAddress,
                          validator: (value) {
                            if (value?.isEmpty ?? true) return 'Please enter email';
                            if (!value!.contains('@')) return 'Invalid email format';
                            return null;
                          },
                        ),
                        _buildTextField(
                          label: 'Phone',
                          controller: _phoneController,
                          icon: Icons.phone_outlined,
                          keyboardType: TextInputType.phone,
                          validator: (value) =>
                              value?.isEmpty ?? true ? 'Please enter phone number' : null,
                        ),
                        _buildTextField(
                          label: 'Address',
                          controller: _addressController,
                          icon: Icons.location_on_outlined,
                          validator: (value) =>
                              value?.isEmpty ?? true ? 'Please enter address' : null,
                        ),
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          height: 50,
                          child: ElevatedButton(
                            onPressed: _isLoading
                                ? null
                                : () {
                                    if (_isEditing) {
                                      _updateProfile();
                                    } else {
                                      setState(() => _isEditing = true);
                                    }
                                  },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: _isEditing
                                  ? AppColors.primaryBlue
                                  : AppColors.primaryBlue.withOpacity(0.8),
                              foregroundColor: Colors.white,
                              elevation: _isEditing ? 4 : 2,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                            child: _isLoading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(
                                    _isEditing ? 'Save Changes' : 'Edit Profile',
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                          ),
                        ),
                        if (_isEditing) ...[
                          const SizedBox(height: 12),
                          TextButton(
                            onPressed: () {
                              setState(() {
                                _isEditing = false;
                                _nameController.text = widget.userData['name'];
                                _emailController.text = widget.userData['email'];
                                _phoneController.text = widget.userData['phone'];
                                _addressController.text = widget.userData['address'];
                              });
                            },
                            style: TextButton.styleFrom(
                              foregroundColor: AppColors.primaryBlue,
                            ),
                            child: const Text('Cancel'),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 10,
              offset: const Offset(0, -5),
            ),
          ],
        ),
        child: BottomNavigationBar(
          items: const <BottomNavigationBarItem>[
            BottomNavigationBarItem(
              icon: Icon(Icons.home_outlined),
              activeIcon: Icon(Icons.home),
              label: 'Home',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.receipt_long_outlined),
              activeIcon: Icon(Icons.receipt_long),
              label: 'Request',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.report_problem_outlined),
              activeIcon: Icon(Icons.report_problem),
              label: 'Complaint',
            ),
          ],
          currentIndex: 0, // Home is selected by default
          onTap: (index) {
            Navigator.pop(context); // Return to home screen
          },
          selectedItemColor: Colors.blue,
          unselectedItemColor: Colors.grey,
          showUnselectedLabels: true,
        ),
      ),
    );
  }
} 