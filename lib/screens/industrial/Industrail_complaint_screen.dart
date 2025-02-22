import 'package:flutter/material.dart';
import 'package:gas/core/constants.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class IndustrailComplaintScreen extends StatefulWidget {
  final Map<String, dynamic> userData;
  
  const IndustrailComplaintScreen({
    Key? key,
    required this.userData,
  }) : super(key: key);

  @override
State<IndustrailComplaintScreen> createState() => _IndustrailComplaintScreenState();
}

        class _IndustrailComplaintScreenState extends State<IndustrailComplaintScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _emailController;
  late TextEditingController _subjectController;
  late TextEditingController _messageController;
  String? selectedOutlet;
  List<Map<String, dynamic>> outlets = [];

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.userData['company_name'] ?? '');
    _emailController = TextEditingController(text: widget.userData['company_email'] ?? '');
    _subjectController = TextEditingController();
    _messageController = TextEditingController();
    fetchOutlets();
  }

  Future<void> fetchOutlets() async {
    try {
      final response = await http.get(
        Uri.parse(ApiConstants.getOutletsEndpoint)
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          setState(() {
            outlets = List<Map<String, dynamic>>.from(data['data']);
          });
        }
      }
    } catch (e) {
      print('Error fetching outlets: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.primaryBlue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.report_problem_outlined,
                    color: AppColors.primaryBlue,
                    size: 24,
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Submit a Complaint',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primaryBlue,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Name Field
            TextFormField(
              controller: _nameController,
              readOnly: true, // Make it read-only since it's pre-filled
              decoration: InputDecoration(
                labelText: 'Name',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: const Icon(Icons.person_outline),
                filled: true,
                fillColor: Colors.grey[100],
              ),
            ),
            const SizedBox(height: 16),

            // Email Field
            TextFormField(
              controller: _emailController,
              readOnly: true, // Make it read-only since it's pre-filled
              decoration: InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: const Icon(Icons.email_outlined),
                filled: true,
                fillColor: Colors.grey[100],
              ),
            ),
            const SizedBox(height: 16),

            // Subject Field
            TextFormField(
              controller: _subjectController,
              decoration: InputDecoration(
                labelText: 'Subject',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: const Icon(Icons.subject),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter a subject';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Outlet Dropdown
            DropdownButtonFormField<String>(
              value: selectedOutlet,
              decoration: InputDecoration(
                labelText: 'Select Outlet',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: const Icon(Icons.store_outlined),
              ),
              items: outlets.map((outlet) {
                return DropdownMenuItem(
                  value: outlet['id'].toString(),
                  child: Text(
                    '${outlet['name']} - ${outlet['location']}',
                    style: const TextStyle(fontSize: 14),
                  ),
                );
              }).toList(),
              onChanged: (value) {
                setState(() {
                  selectedOutlet = value;
                });
              },
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please select an outlet';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Message Field
            TextFormField(
              controller: _messageController,
              maxLines: 5,
              decoration: InputDecoration(
                labelText: 'Message',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                alignLabelWithHint: true,
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your message';
                }
                return null;
              },
            ),
            const SizedBox(height: 24),

            // Submit Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _submitComplaint,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primaryBlue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: const Text(
                  'Submit Complaint',
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
    );
  }

  void _submitComplaint() async {
    if (_formKey.currentState!.validate()) {
      try {
        final response = await http.post(
          Uri.parse(ApiConstants.submitComplaintEndpoint),
          body: {
            'email': _emailController.text,
            'name': _nameController.text,
            'subject': _subjectController.text,
            'message': _messageController.text,
            'outlet_id': selectedOutlet,
            'status': 'not replied'
          },
        );

        if (response.statusCode == 200) {
          // Show success dialog
          showDialog(
            context: context,
            builder: (BuildContext context) {
              return AlertDialog(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                title: Row(
                  children: [
                    Icon(
                      Icons.check_circle_outline,
                      color: Colors.green,
                      size: 28,
                    ),
                    SizedBox(width: 8),
                    Text('Thank You'),
                  ],
                ),
                content: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Your complaint has been submitted successfully.',
                      style: TextStyle(fontSize: 16),
                    ),
                    SizedBox(height: 8),
                    Text(
                      'We will review and respond to your complaint as soon as possible.',
                      style: TextStyle(
                        fontSize: 14,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
                actions: [
                  TextButton(
                    onPressed: () {
                      Navigator.of(context).pop();
                      // Clear form fields
                      _subjectController.clear();
                      _messageController.clear();
                      setState(() {
                        selectedOutlet = null;
                      });
                    },
                    child: Text(
                      'OK',
                      style: TextStyle(
                        color: AppColors.primaryBlue,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              );
            },
          );
        } else {
          // Show error dialog
          showDialog(
            context: context,
            builder: (BuildContext context) {
              return AlertDialog(
                title: Row(
                  children: [
                    Icon(
                      Icons.error_outline,
                      color: Colors.red,
                      size: 28,
                    ),
                    SizedBox(width: 8),
                    Text('Error'),
                  ],
                ),
                content: Text(
                  'Failed to submit complaint. Please try again later.',
                  style: TextStyle(fontSize: 16),
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.of(context).pop(),
                    child: Text(
                      'OK',
                      style: TextStyle(
                        color: AppColors.primaryBlue,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              );
            },
          );
        }
      } catch (e) {
        // Show error dialog for network/other errors
        showDialog(
          context: context,
          builder: (BuildContext context) {
            return AlertDialog(
              title: Row(
                children: [
                  Icon(
                    Icons.error_outline,
                    color: Colors.red,
                    size: 28,
                  ),
                  SizedBox(width: 8),
                  Text('Error'),
                ],
              ),
              content: Text(
                'An error occurred. Please check your internet connection and try again.',
                style: TextStyle(fontSize: 16),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: Text(
                    'OK',
                    style: TextStyle(
                      color: AppColors.primaryBlue,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            );
          },
        );
      }
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }
} 