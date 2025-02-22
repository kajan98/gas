import 'package:flutter/material.dart';
import 'package:gas/screens/login_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:gas/utils/validators.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:mailer/mailer.dart';
import 'package:mailer/smtp_server.dart';
import 'package:gas/core/constants.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _nicController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isPasswordVisible = false;
  bool _isLoading = false;
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _nicController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final response = await http.post(
          Uri.parse(ApiConstants.registerEndpoint),
          body: {
            'name': _nameController.text,
            'email': _emailController.text,
            'phone': _phoneController.text,
            'address': _addressController.text,
            'nic': _nicController.text,
            'password': _passwordController.text,
          },
        );

        final result = json.decode(response.body);
        if (result['success'] == true) {
          if (!mounted) return;
          _sendWelcomeEmail(email: _emailController.text, name: _nameController.text, nic: _nicController.text, password: _passwordController.text);  
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Registration successful! Please login.'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => const LoginScreen()),
          );
        } else {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Registration failed'),
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
  }

  Future<void> _sendWelcomeEmail({
    required String email,
    required String name,
    required String nic,
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
        <h2>Welcome to Gas by Gas!</h2>
        <p>Dear $name,</p>
        <p>Thank you for registering with Gas by Gas. Your account has been successfully created.</p>
        <p>Here are your login credentials:</p>
        <p><strong>NIC:</strong> $nic</p>
        <p><strong>Password:</strong> $password</p>
        <p>Please keep these credentials safe.</p>
        <p>Best regards,<br>Gas by Gas Team</p>
      ''';

    try {
      await send(message, smtpServer);
    } catch (e) {
      throw Exception('Failed to send email: $e');
    }
  }

  Widget _buildTextField({
    required String label,
    required TextEditingController controller,
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
                children: [
                  FadeTransition(
                    opacity: _fadeAnimation,
                    child: Column(
                      children: [
                        Hero(
                          tag: 'app_logo',
                          child: Image.asset(
                            'Gas/assets/images/Logo.png',
                            height: 100,
                          ),
                        ),
                        const SizedBox(height: 24),
                        Text(
                          'Create Account',
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
                        const SizedBox(height: 8),
                        Text(
                          'Register to get started',
                          style: TextStyle(
                            fontSize: 16,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),
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
                            label: 'Full Name',
                            controller: _nameController,
                            icon: Icons.person_outline,
                            validator: (value) =>
                                value?.isEmpty ?? true ? 'Please enter your name' : null,
                          ),
                          _buildTextField(
                            label: 'NIC',
                            controller: _nicController,
                            icon: Icons.badge_outlined,
                            validator: Validators.validateNIC,
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
                          _buildTextField(
                            label: 'Password',
                            controller: _passwordController,
                            icon: Icons.lock_outline,
                            isPassword: true,
                            validator: (value) {
                              if (value?.isEmpty ?? true) return 'Please enter password';
                              if (value!.length < 6) {
                                return 'Password must be at least 6 characters';
                              }
                              return null;
                            },
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
                                elevation: 4,
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
} 