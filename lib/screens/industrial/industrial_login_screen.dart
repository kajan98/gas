import 'package:flutter/material.dart';
import 'package:gas/screens/industrial/industrial_home_screen.dart';
import 'package:gas/screens/industrial/industrial_register_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:gas/core/constants.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:gas/utils/custom_dialog.dart';

class IndustrialLoginScreen extends StatefulWidget {
  const IndustrialLoginScreen({super.key});

  @override
  State<IndustrialLoginScreen> createState() => _IndustrialLoginScreenState();
}

class _IndustrialLoginScreenState extends State<IndustrialLoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _nicController = TextEditingController(text: '123456789');
  final _passwordController = TextEditingController(text: '123456789');
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
    _nicController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isLoading = true);
      try {
        final response = await http.post(
          Uri.parse('${ApiConstants.baseUrl}/industrial_login.php'),
          body: {
            'business_registration_number': _nicController.text.trim(),
            'password': _passwordController.text,
          },
        );

        final result = json.decode(response.body);
        
        if (!mounted) return;

        if (result['pending'] == true) {
          await CustomDialog.show(
            context: context,
            title: 'Account Pending',
            message: 'Your account is pending approval. Please wait for admin verification.',
            isWarning: true,
          );
        } else if (result['success'] == true) {
          await CustomDialog.show(
            context: context,
            title: 'Success',
            message: 'Login successful!',
            isSuccess: true,
            onOkPressed: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(
                  builder: (context) => IndustrialHomeScreen(userData: result['user']),
                ),
              );
            },
          );
        } else {
          await CustomDialog.show(
            context: context,
            title: 'Error',
            message: result['message'] ?? 'Login failed',
            isError: true,
          );
        }
      } catch (e) {
        if (!mounted) return;
        await CustomDialog.show(
          context: context,
          title: 'Error',
          message: 'Connection error: $e',
          isError: true,
        );
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.primaryBlue),
          onPressed: () => Navigator.pop(context),
        ),
      ),
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
                  const SizedBox(height: 40),
                  FadeTransition(
                    opacity: _fadeAnimation,
                    child: Column(
                      children: [
                        Hero(
                          tag: 'app_logo',
                          child: Image.asset(
                            'Gas/assets/images/Logo.png',
                            height: 120,
                          ),
                        ),
                        const SizedBox(height: 24),
                        Text(
                          'Industrial Login',
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
                          'Welcome back to your business account',
                          style: TextStyle(
                            fontSize: 16,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                  Card(
                    elevation: 8,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        children: [
                          TextFormField(
                            controller: _nicController,
                            decoration: InputDecoration(
                              labelText: 'Business Registration Number',
                              prefixIcon: Icon(Icons.business, color: AppColors.primaryBlue),
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
                          ),
                          const SizedBox(height: 20),
                          TextFormField(
                            controller: _passwordController,
                            obscureText: !_isPasswordVisible,
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: Icon(Icons.lock_outline, color: AppColors.primaryBlue),
                              suffixIcon: IconButton(
                                icon: Icon(
                                  _isPasswordVisible ? Icons.visibility_off : Icons.visibility,
                                  color: AppColors.primaryBlue,
                                ),
                                onPressed: () {
                                  setState(() {
                                    _isPasswordVisible = !_isPasswordVisible;
                                  });
                                },
                              ),
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
                            validator: (value) =>
                                value?.isEmpty ?? true ? 'Please enter password' : null,
                          ),
                          const SizedBox(height: 12),
                         
                          const SizedBox(height: 24),
                          SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _handleLogin,
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
                                      'Login',
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
                        'New Industrial User? ',
                        style: TextStyle(color: AppColors.textSecondary),
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const IndustrialRegisterScreen(),
                            ),
                          );
                        },
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.primaryBlue,
                        ),
                        child: const Text(
                          'Register',
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