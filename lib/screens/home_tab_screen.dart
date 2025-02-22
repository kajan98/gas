import 'package:flutter/material.dart';
import 'package:gas/core/constants.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:provider/provider.dart';
import 'package:gas/providers/cart_provider.dart';
import 'package:gas/providers/outlet_provider.dart';
import 'package:gas/screens/request_screen.dart';


class HomeTabScreen extends StatefulWidget {
  final Map<String, dynamic> userData;

  const HomeTabScreen({
    Key? key, 
    required this.userData,
  }) : super(key: key);

  @override
  State<HomeTabScreen> createState() => _HomeTabScreenState();
}

class _HomeTabScreenState extends State<HomeTabScreen> {
  Map<String, dynamic>? selectedOutlet;
  List<Map<String, dynamic>> outlets = [];
  List<Map<String, dynamic>> stockData = [];

  @override
  void initState() {
    super.initState();
    fetchOutlets().then((_) {
      // Set first outlet as default if available
      if (outlets.isNotEmpty && selectedOutlet == null) {
        setState(() {
          selectedOutlet = outlets.first;
          fetchStockByOutlet(selectedOutlet!['outlet_name']);
        });
        // Add this line to update the OutletProvider
        context.read<OutletProvider>().setSelectedOutlet(selectedOutlet!);
      }
    });
  }

  Future<void> fetchOutlets() async {
    try {
      final response = await http.get(Uri.parse(ApiConstants.getStockEndpoint));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          outlets = List<Map<String, dynamic>>.from(data['outlets']);
          if (outlets.isNotEmpty && selectedOutlet == null) {
            selectedOutlet = outlets.first;
            fetchStockByOutlet(selectedOutlet!['outlet_name']);
          }
          stockData = List<Map<String, dynamic>>.from(data['stock']);
        });
      }
    } catch (e) {
      print('Error fetching data: $e');
    }
  }

  Future<void> fetchStockByOutlet(String outlet) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl1}/api/get_stock.php?outlet_name=$outlet'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          stockData = List<Map<String, dynamic>>.from(data['stock']);
        });
      }
    } catch (e) {
      print('Error fetching stock: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Outlet Dropdown
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: DropdownButtonFormField<String>(
            value: selectedOutlet?['outlet_name'],
            decoration: InputDecoration(
              labelText: 'Select Outlet',
              border: OutlineInputBorder(),
              filled: true,
              fillColor: Colors.white,
            ),
            items: outlets.map<DropdownMenuItem<String>>((outlet) {
              return DropdownMenuItem<String>(
                value: outlet['outlet_name'].toString(),
                child: Text(outlet['outlet_name'].toString()),
              );
            }).toList(),
            onChanged: (value) {
              setState(() {
                selectedOutlet = outlets.firstWhere((o) => o['outlet_name'] == value);
                fetchStockByOutlet(value!);
              });
              // Add this line to update the OutletProvider
              context.read<OutletProvider>().setSelectedOutlet(selectedOutlet!);
            },
          ),
        ),

        // Stock Cards
        Expanded(
          child: GridView.builder(
            padding: const EdgeInsets.all(16),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 0.8,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
            ),
            itemCount: stockData.length,
            itemBuilder: (context, index) {
              final item = stockData[index];
              final stockQuantity = int.tryParse(item['stock_quantity'].toString()) ?? 0;
              
              return Card(
                elevation: 4,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.gas_meter, size: 48, color: AppColors.primaryBlue),
                    Text(
                      item['pack_name'],
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    Text('LKR.${item['max_retail_price']}'),
                    Text('Stock: $stockQuantity'),
                    SizedBox(height: 10),
                    ElevatedButton(
                      onPressed: stockQuantity > 0
                          ? () => context.read<CartProvider>().addToCart(item, context,)
                          : null,
                      child: Text('Add to Cart'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryBlue,
                        foregroundColor: Colors.white,
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  // When outlet is selected in home screen
  void onOutletSelected(Map<String, dynamic> outlet) {
    setState(() {
      selectedOutlet = outlet;
    });
    // Add this line to update the OutletProvider
    context.read<OutletProvider>().setSelectedOutlet(outlet);
  }

  void navigateToRequestScreen() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => RequestScreen(
          selectedOutlet: selectedOutlet!,
          userData: widget.userData,
        ),
      ),
    );
  }
} 