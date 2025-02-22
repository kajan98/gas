import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:gas/screens/industrial/industrial_login_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:gas/core/constants.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'package:mailer/mailer.dart';
import 'package:mailer/smtp_server.dart';

class IndustrialRegisterScreen extends StatefulWidget {
  const IndustrialRegisterScreen({super.key});

  @override
  State<IndustrialRegisterScreen> createState() => _IndustrialRegisterScreenState();
}

class _IndustrialRegisterScreenState extends State<IndustrialRegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _companyNameController = TextEditingController();
  final _nicController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isPasswordVisible = false;
  bool _isLoading = false;
  File? _certificateFile;
  String? _fileName;
  List<int>? _certificateBytes;

  Future<void> _pickFile() async {
    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        withData: true,
      );

      if (result != null) {
        setState(() {
          if (kIsWeb) {
            _certificateFile = null;
            _certificateBytes = result.files.single.bytes;
          } else {
            _certificateFile = File(result.files.single.path!);
            _certificateBytes = null;
          }
          _fileName = result.files.single.name;
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error picking file: $e'),
          backgroundColor: AppColors.primaryBlue,
        ),
      );
    }
  }

  Future<void> _sendRegistrationEmail({
    required String email,
    required String companyName,
    required String businessRegistrationNumber,
    required String password,
  }) async {
    final smtpServer = gmail(
      'gagbygas@gmail.com',
      'czzl qfge avup oxmc',
    );

    final message = Message()
      ..from = Address('gagbygas@gmail.com', 'Gas by Gas')
      ..recipients.add(email)
      ..subject = 'Welcome to Gas by Gas!'
      ..html = '''
        <h2>Dear $companyName,</h2>
        <p>Thank you for registering with our Gas Distribution System.</p>
        <p>Your registration is currently under review. Our team will process your application within the next 2 hours.</p>
        <p>Once approved, you will be able to access all industrial user features.</p>
        <br>
        <p>Best regards,</p>
        <p>Gas Distribution System Team</p>
      ''';

    try {
      await send(message, smtpServer);
    } catch (e) {
      throw Exception('Failed to send email: $e');
    }
  }

  Future<void> _register() async {
    if (_formKey.currentState!.validate()) {
      if (_certificateFile == null && _certificateBytes == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Please upload business registration certificate'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

      setState(() => _isLoading = true);
      try {
        // Create multipart request
        var request = http.MultipartRequest(
          'POST',
          Uri.parse('${ApiConstants.baseUrl}/industrial_register.php'),
        );

        // Add text fields
        request.fields.addAll({
          'company_name': _companyNameController.text.trim(),
          'business_registration_number': _nicController.text.trim(),
          'phone_number': _phoneController.text.trim(),
          'company_email': _emailController.text.trim(),
          'password': _passwordController.text,
        });

        // Add certificate file
        if (_certificateFile != null) {
          request.files.add(await http.MultipartFile.fromPath(
            'business_registration_certificate',
            _certificateFile!.path,
            filename: _fileName,
          ));
        } else if (_certificateBytes != null) {
          request.files.add(http.MultipartFile.fromBytes(
            'business_registration_certificate',
            _certificateBytes!,
            filename: _fileName,
          ));
        }

        // Send request and handle response with better error handling
        try {
          var streamedResponse = await request.send();
          var response = await http.Response.fromStream(streamedResponse);
          
          // Debug response
          print('Response status: ${response.statusCode}');
          print('Response body: ${response.body}');

          // Check if response is empty
          if (response.body.isEmpty) {
            throw Exception('Empty response from server');
          }

          // Try to parse response
          Map<String, dynamic> responseData;
          try {
            responseData = json.decode(response.body);
          } catch (e) {
            throw Exception('Invalid response format: ${response.body}');
          }

          if (!mounted) return;

          if (responseData['success'] == true) {
            // Send confirmation email
            try {
              await _sendRegistrationEmail(
                email: _emailController.text,
                companyName: _companyNameController.text,
                businessRegistrationNumber: _nicController.text,
                password: _passwordController.text,
              );
            } catch (emailError) {
              print('Email sending failed: $emailError');
              // Continue with registration success even if email fails
            }

            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Registration successful! Please check your email for confirmation.'),
                backgroundColor: Colors.green,
                duration: Duration(seconds: 3),
              ),
            );

            // Navigate to login screen
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const IndustrialLoginScreen()),
            );
          } else {
            throw Exception(responseData['message'] ?? 'Registration failed');
          }
        } catch (networkError) {
          throw Exception('Network error: $networkError');
        }
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            backgroundColor: AppColors.primaryBlue,
            duration: const Duration(seconds: 3),
          ),
        );
      } finally {
        if (mounted) {
          setState(() => _isLoading = false);
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppColors.primaryBlue.withOpacity(0.1),
              Colors.white,
              AppColors.primaryBlue.withOpacity(0.05),
            ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Industrial Registration',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primaryBlue,
                      shadows: [
                        Shadow(
                          color: AppColors.primaryBlue.withOpacity(0.3),
                          offset: const Offset(1, 1),
                          blurRadius: 2,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  Card(
                    elevation: 8,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        children: [
                          _buildTextField(
                            controller: _companyNameController,
                            label: 'Company Name',
                            icon: Icons.business,
                            validator: (value) =>
                                value?.isEmpty ?? true ? 'Please enter company name' : null,
                          ),
                          _buildTextField(
                            controller: _nicController,
                            label: 'Business Registration Number',
                            icon: Icons.numbers,
                            validator:(value) =>
                                value?.isEmpty ?? true ? 'Please enter Registration Number' : null,
                          ),
                          _buildTextField(
                            controller: _phoneController,
                            label: 'Phone Number',
                            icon: Icons.phone,
                            keyboardType: TextInputType.phone,
                            validator: (value) =>
                                value?.isEmpty ?? true ? 'Please enter phone number' : null,
                          ),
                          _buildTextField(
                            controller: _emailController,
                            label: 'Company Email',
                            icon: Icons.email,
                            keyboardType: TextInputType.emailAddress,
                            validator: (value) {
                              if (value?.isEmpty ?? true) return 'Please enter email';
                              if (!value!.contains('@')) return 'Invalid email format';
                              return null;
                            },
                          ),
                          _buildTextField(
                            controller: _passwordController,
                            label: 'Password',
                            icon: Icons.lock,
                            isPassword: true,
                            validator: (value) {
                              if (value?.isEmpty ?? true) return 'Please enter password';
                              if (value!.length < 6) {
                                return 'Password must be at least 6 characters';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 20),
                          OutlinedButton.icon(
                            onPressed: _pickFile,
                            icon: Icon(Icons.upload_file, color: AppColors.primaryBlue),
                            label: Text(
                              _fileName ?? 'Upload Business Registration Certificate',
                              style: TextStyle(color: AppColors.primaryBlue),
                            ),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.all(16),
                              side: BorderSide(color: AppColors.primaryBlue),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),
                          SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _register,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryBlue,
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                elevation: 4,
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
                                  : const Text(
                                      'Register',
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Already have an account? ',
                        style: TextStyle(color: AppColors.textSecondary),
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.pop(context);
                        },
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.primaryBlue,
                        ),
                        child: const Text(
                          'Login',
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    String? Function(String?)? validator,
    TextInputType? keyboardType,
    bool isPassword = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: TextFormField(
        controller: controller,
        obscureText: isPassword && !_isPasswordVisible,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon, color: AppColors.primaryBlue),
          suffixIcon: isPassword
              ? IconButton(
                  icon: Icon(
                    _isPasswordVisible ? Icons.visibility_off : Icons.visibility,
                    color: AppColors.primaryBlue,
                  ),
                  onPressed: () {
                    setState(() {
                      _isPasswordVisible = !_isPasswordVisible;
                    });
                  },
                )
              : null,
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
        ),
        validator: validator,
      ),
    );
  }
} 