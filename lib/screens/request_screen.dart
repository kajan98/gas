import 'package:flutter/material.dart';
import 'package:gas/core/constants.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:provider/provider.dart';
import 'package:gas/providers/cart_provider.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';



class RequestScreen extends StatelessWidget {
  final Map<String, dynamic> selectedOutlet;
  final Map<String, dynamic> userData;

  const RequestScreen({
    Key? key, 
    required this.selectedOutlet,
    required this.userData,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Consumer<CartProvider>(
      builder: (context, cartProvider, child) {
        // Ensure consumer mode is set
        cartProvider.isIndustrial = false;

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                children: [
                  // User info
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.person, color: Colors.blue),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Customer Name',
                                style: TextStyle(
                                  color: Colors.grey.shade600,
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                userData['name'] ?? 'Unknown User',
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Existing outlet info container
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.store, color: Colors.blue),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Selected Outlet',
                                style: TextStyle(
                                  color: Colors.grey.shade600,
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                selectedOutlet['outlet_name'] ?? 'No outlet selected',
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: cartProvider.cartItems.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.shopping_cart_outlined,
                            size: 84,
                            color: AppColors.primaryBlue.withOpacity(0.5),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'Your cart is empty',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w500,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Add items to get started',
                            style: TextStyle(
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: cartProvider.cartItems.length,
                      padding: const EdgeInsets.all(16),
                      itemBuilder: (context, index) {
                        final item = cartProvider.cartItems[index];
                        final quantity = item['quantity'] ?? 0;
                        final price = double.tryParse(item['max_retail_price'].toString()) ?? 0.0;
                        final totalItemPrice = price * quantity;

                        return Card(
                          elevation: 2,
                          margin: const EdgeInsets.only(bottom: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                            side: BorderSide(
                              color: AppColors.primaryBlue.withOpacity(0.1),
                            ),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(12),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryBlue.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Icon(
                                        Icons.gas_meter_rounded,
                                        color: AppColors.primaryBlue,
                                        size: 32,
                                      ),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            item['pack_name'],
                                            style: const TextStyle(
                                              fontSize: 18,
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            'LKR ${price.toStringAsFixed(2)}',
                                            style: TextStyle(
                                              fontSize: 16,
                                              color: AppColors.textSecondary,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: () => cartProvider.removeItem(index),
                                      icon: const Icon(Icons.delete_outline),
                                      color: Colors.red,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 16),
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: AppColors.primaryBlue.withOpacity(0.05),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Row(
                                        children: [
                                          IconButton(
                                            onPressed: quantity > 1
                                                ? () => cartProvider.updateQuantity(index, false)
                                                : null,
                                            icon: const Icon(Icons.remove_circle_outline),
                                            color: AppColors.primaryBlue,
                                          ),
                                          Container(
                                            padding: const EdgeInsets.symmetric(
                                              horizontal: 16,
                                              vertical: 8,
                                            ),
                                            decoration: BoxDecoration(
                                              color: Colors.white,
                                              borderRadius: BorderRadius.circular(6),
                                              border: Border.all(
                                                color: AppColors.primaryBlue,
                                              ),
                                            ),
                                            child: Text(
                                              quantity.toString(),
                                              style: const TextStyle(
                                                fontWeight: FontWeight.bold,
                                                fontSize: 16,
                                              ),
                                            ),
                                          ),
                                          IconButton(
                                            onPressed: quantity < 5
                                                ? () => cartProvider.updateQuantity(index, true)
                                                : null,
                                            icon: const Icon(Icons.add_circle_outline),
                                            color: AppColors.primaryBlue,
                                          ),
                                        ],
                                      ),
                                      Text(
                                        'LKR ${totalItemPrice.toStringAsFixed(2)}',
                                        style: TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.primaryBlue,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
            if (cartProvider.cartItems.isNotEmpty)
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primaryBlue.withOpacity(0.1),
                      blurRadius: 20,
                      offset: const Offset(0, -5),
                    ),
                  ],
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Total Amount',
                            style: TextStyle(
                              fontSize: 16,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          Text(
                            'LKR ${cartProvider.totalPrice.toStringAsFixed(2)}',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primaryBlue,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () async {
                            // Debug prints
                            print('=== Request Data ===');
                            print('User ID: ${userData['id']}');
                            print('User Name: ${userData['name']}');
                            print('Outlet Name: ${selectedOutlet['outlet_name']}');
                            print('Cart Items:');
                            cartProvider.cartItems.forEach((item) {
                              print('- ${item['pack_name']}: ${item['quantity']}');
                            });
                            print('==================');

                            try {
                              final response = await http.post(
                                Uri.parse('${ApiConstants.baseUrl}/submit_request.php'),
                                body: {
                                  'outlet_name': selectedOutlet['outlet_name'].toString(),
                                  'cart_items': json.encode(cartProvider.cartItems),
                                  'user_id': userData['id'].toString(),
                                },
                              );

                              print('API Response: ${response.body}');
                              final data = json.decode(response.body);
                              if (data['status'] == 'success') {
                                cartProvider.clearCart();
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Request submitted successfully'),
                                    backgroundColor: Colors.green,
                                  ),
                                );
                              
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(data['message'] ?? 'Failed to submit request'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            } catch (error) {
                              print('Error: $error');
                        
                            }
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primaryBlue,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text(
                            'Proceed to Checkout',
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
          ],
        );
      },
    );
  }
} 