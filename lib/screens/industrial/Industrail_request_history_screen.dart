import 'package:flutter/material.dart';
import 'package:gas/core/constants.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class IndustrailRequestHistoryScreen extends StatefulWidget {
  final int userId;

  const IndustrailRequestHistoryScreen({Key? key, required this.userId}) : super(key: key);

  @override
  _IndustrailRequestHistoryScreenState createState() => _IndustrailRequestHistoryScreenState();
}

class _IndustrailRequestHistoryScreenState extends State<IndustrailRequestHistoryScreen> {
  List<dynamic> requests = [];
  List<dynamic> filteredRequests = [];
  String filterStatus = 'all'; // Default filter
  TextEditingController searchController = TextEditingController();
  bool isLoading = true; // Loading state

  @override
  void initState() {
    super.initState();
    fetchRequests();
  }

  Future<void> fetchRequests() async {
    setState(() {
      isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse(ApiConstants.getIndustrialRequestsEndpoint),
        body: {
          'user_id': widget.userId.toString(),
        },
      );

      print('Response status: ${response.statusCode}');
      print('Response body: ${response.body}');

      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonResponse = json.decode(response.body);
        if (jsonResponse['status'] == 'success') {
          setState(() {
            requests = List<dynamic>.from(jsonResponse['data']);
            filteredRequests = requests;
            isLoading = false;
          });
        } else {
          throw Exception(jsonResponse['message'] ?? 'Failed to load requests');
        }
      } else {
        throw Exception('Failed to load requests');
      }
    } catch (e) {
      setState(() {
        isLoading = false;
      });
      print('Error fetching requests: $e');
    }
  }

  void filterRequests() {
    setState(() {
      filteredRequests = requests.where((request) {
        final matchesStatus = filterStatus == 'all' || request['status'] == filterStatus;
        final matchesSearch = request['pack_name'].toLowerCase().contains(searchController.text.toLowerCase());
        return matchesStatus && matchesSearch;
      }).toList();
    });
  }

  Color getStatusColor(String status) {
    switch (status) {
      case 'requested':
        return Colors.blue;
      case 'allocated':
        return Colors.green;
      case 'completed':
        return Colors.orange;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Request History'),
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator()) // Show loading indicator
          : Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Dropdown for filtering
                  DropdownButtonFormField<String>(
                    value: filterStatus,
                    decoration: InputDecoration(
                      labelText: 'Filter by Status',
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      DropdownMenuItem(value: 'all', child: Text('All')),
                      DropdownMenuItem(value: 'requested', child: Text('Requested')),
                      DropdownMenuItem(value: 'allocated', child: Text('Allocated')),
                      DropdownMenuItem(value: 'completed', child: Text('Completed')),
                      DropdownMenuItem(value: 'cancelled', child: Text('Cancelled')),
                    ],
                    onChanged: (value) {
                      setState(() {
                        filterStatus = value!;
                        filterRequests(); // Filter when status changes
                      });
                    },
                  ),
                  const SizedBox(height: 16),
                  // Search Field
                  TextField(
                    controller: searchController,
                    decoration: InputDecoration(
                      labelText: 'Search by Pack Name',
                      border: OutlineInputBorder(),
                    ),
                    onChanged: (value) {
                      filterRequests(); // Filter when search text changes
                    },
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: filteredRequests.isEmpty
                        ? Center(child: Text('No requests found.'))
                        : ListView.builder(
                            itemCount: filteredRequests.length,
                            itemBuilder: (context, index) {
                              final request = filteredRequests[index];
                              return Card(
                                child: ListTile(
                                  title: Text(request['pack_name']),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('Quantity: ${request['quantity']}'),
                                      Text(
                                        'Status: ${request['status']}',
                                        style: TextStyle(
                                          color: getStatusColor(request['status']),
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ],
                                  ),
                        
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
    );
  }
} 