import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'home_screen.dart';
import 'register_screen.dart';
import 'package:gas/utils/validators.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:gas/core/constants.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import 'dart:math';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _nicController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isPasswordVisible = false;
  bool _isLoading = false;
  bool _isSendingCode = false;
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  final _forgotEmailController = TextEditingController();
  final _forgotPhoneController = TextEditingController();
  final _verificationCodeController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _nicController.text = '199822210303';
    _passwordController.text = '123456';
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
          Uri.parse(ApiConstants.loginEndpoint),
          body: {
            'nic': _nicController.text,
            'password': _passwordController.text,
          },
        );

        final result = json.decode(response.body);
        if (result['success'] == true) {
          if (!mounted) return;
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (context) => HomeScreen(userData: result['user']),
            ),
          );
        } else {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Login failed'),
              backgroundColor: AppColors.primaryBlue,
            ),
          );
        }
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Connection error: $e'),
            backgroundColor: AppColors.primaryBlue,
          ),
        );
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _showForgotPasswordDialog() async {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('Forgot Password'),
            IconButton(
              icon: Icon(Icons.close),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _forgotEmailController,
              decoration: InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.email),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: _forgotPhoneController,
              decoration: InputDecoration(
                labelText: 'Phone Number',
                prefixIcon: Icon(Icons.phone),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: _isSendingCode ? null : _sendVerificationCode,
            child: _isSendingCode
                ? CircularProgressIndicator(color: Colors.white)
                : Text('Send Code'),
          ),
        ],
      ),
    );
  }

  Future<void> _sendVerificationCode() async {
    if (_forgotEmailController.text.isEmpty || _forgotPhoneController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter both email and phone number'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    try {
      setState(() {
        _isSendingCode = true;
      });
      
      final random = Random();
      const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      const numbers = '0123456789';

      final code = List.generate(3, (index) => letters[random.nextInt(letters.length)]).join() +
                   List.generate(3, (index) => numbers[random.nextInt(numbers.length)]).join();
      
      final response = await http.post(
        Uri.parse(ApiConstants.sendVerificationCodeEndpoint),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'email': _forgotEmailController.text.trim(),
          'phone': _forgotPhoneController.text.trim(),
          'code': code,
          'created_at': DateTime.now().toIso8601String(),
          'expires_at': DateTime.now().add(const Duration(minutes: 10)).toIso8601String(),
          'is_used': '0',
        },
      );

      print('Response body: ${response.body}');

      if (!mounted) return;

      final result = json.decode(response.body);
      if (result['success'] == true) {
        Navigator.pop(context); // Close the forgot password dialog
        _showVerificationDialog(_forgotEmailController.text.trim());
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Failed to send verification code'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      print('Error sending verification code: $e'); // Print the error
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error sending verification code: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSendingCode = false;
        });
      }
    }
  }

  void _showVerificationDialog(String email) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('Verify Code'),
            IconButton(
              icon: Icon(Icons.close),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Verification code has been sent to $email'),
            SizedBox(height: 16),
            PinCodeTextField(
              appContext: context,
              length: 6,
              controller: _verificationCodeController,
              onChanged: (value) {},
              pinTheme: PinTheme(
                shape: PinCodeFieldShape.box,
                borderRadius: BorderRadius.circular(8),
                activeColor: AppColors.primaryBlue,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: _sendVerificationCode,
            child: Text('Resend Code'),
          ),
          ElevatedButton(
            onPressed: () => _verifyCode(),
            child: Text('Verify'),
          ),
        ],
      ),
    );
  }

  Future<void> _verifyCode() async {
    try {
      final response = await http.post(
        Uri.parse(ApiConstants.verifyCodeEndpoint),
        body: {
          'email': _forgotEmailController.text,
          'phone': _forgotPhoneController.text,
          'code': _verificationCodeController.text,
        },
      );

      final result = json.decode(response.body);
      if (result['success']) {
        Navigator.pop(context); // Close verification dialog
        _showResetPasswordDialog(result['user_id'].toString());
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'])),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  void _showResetPasswordDialog(String userId) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('Reset Password'),
            IconButton(
              icon: Icon(Icons.close),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _newPasswordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'New Password',
                prefixIcon: Icon(Icons.lock),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: _confirmPasswordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Confirm Password',
                prefixIcon: Icon(Icons.lock),
              ),
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () => _resetPassword(userId),
            child: Text('Reset Password'),
          ),
        ],
      ),
    );
  }

  Future<void> _resetPassword(String userId) async {
    if (_newPasswordController.text != _confirmPasswordController.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Passwords do not match')),
      );
      return;
    }

    try {
      final response = await http.post(
        Uri.parse(ApiConstants.resetPasswordEndpoint),
        body: {
          'user_id': userId,
          'new_password': _newPasswordController.text,
        },
      );

      final result = json.decode(response.body);
      if (result['success']) {
        Navigator.pop(context); // Close reset password dialog
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Password reset successfully')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'])),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading:  IconButton(
                icon: Icon(Icons.arrow_back_ios, color: AppColors.primaryBlue),
                onPressed: () => Navigator.pop(context),
              )
           
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
                          'Welcome Back!',
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
                          'Sign in to continue',
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
                              labelText: 'NIC',
                              prefixIcon: Icon(Icons.person_outline, color: AppColors.primaryBlue),
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
                            validator: Validators.validateNIC,
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
                          TextButton(
                            onPressed: _showForgotPasswordDialog,
                            child: Text(
                              'Forgot Password?',
                              style: TextStyle(color: AppColors.primaryBlue),
                            ),
                          ),
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
                        'Don\'t have an account? ',
                        style: TextStyle(color: AppColors.textSecondary),
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const RegisterScreen(),
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