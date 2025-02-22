import 'package:flutter/material.dart';
import 'package:gas/screens/industrial/Industrail_complaint_screen.dart';
import 'package:gas/screens/industrial/Industrail_profile_screen.dart';
import 'package:gas/screens/industrial/Industrail_request_history_screen.dart';
import 'package:gas/screens/industrial/Industrail_request_screen.dart';
import 'package:gas/screens/industrial/industrail_home_tab_screen.dart';
import 'package:gas/screens/industrial/industrial_login_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:provider/provider.dart';
import 'package:gas/providers/cart_provider.dart';
import 'package:gas/providers/outlet_provider.dart';

class IndustrialHomeScreen extends StatefulWidget {
  final Map<String, dynamic> userData;

  const IndustrialHomeScreen({super.key, required this.userData});

  @override
  State<IndustrialHomeScreen> createState() => _IndustrialHomeScreenState();
}

class _IndustrialHomeScreenState extends State<IndustrialHomeScreen> {
  int _selectedIndex = 0;
  late List<Widget> _screens;
  late List<String> _titles;

  @override
  void initState() {
    super.initState();
    // Set industrial mode for cart
    Future.microtask(() {
      Provider.of<CartProvider>(context, listen: false).isIndustrial = true;
    });

    _screens = [
      IndustrialHomeTabScreen(userData: widget.userData),
      Consumer<OutletProvider>(
        builder: (context, outletProvider, child) => IndustrialRequestScreen(
          selectedOutlet: outletProvider.selectedOutlet ?? {'outlet_name': 'No outlet selected'},
          userData: widget.userData,
        ),
      ),
      IndustrailComplaintScreen(userData: widget.userData),
    ];
    _titles = ['Home', 'Cart', 'Message'];
  }

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Text(
          _titles[_selectedIndex],
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: AppColors.primaryBlue,
        elevation: 0,
        actions: [
          // Cart badge
          if (_selectedIndex != 1) // Don't show cart icon in cart screen
            Consumer<CartProvider>(
              builder: (context, cart, child) {
                return Stack(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.shopping_cart_outlined),
                      onPressed: () {
                        setState(() => _selectedIndex = 1);
                      },
                    ),
                    if (cart.itemCount > 0)
                      Positioned(
                        right: 0,
                        top: 0,
                        child: Container(
                          padding: const EdgeInsets.all(2),
                          decoration: BoxDecoration(
                            color: Colors.red,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          constraints: const BoxConstraints(
                            minWidth: 20,
                            minHeight: 20,
                          ),
                          child: Text(
                            '${cart.itemCount}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
          PopupMenuButton<String>(
            icon: const CircleAvatar(
              backgroundColor: Colors.white,
              child: Icon(Icons.person, color: AppColors.primaryBlue),
            ),
            onSelected: (value) {
              if (value == 'profile') {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => IndustrailProfileScreen(userData: widget.userData),
                  ),
                );
              } else if (value == 'request_history') {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => IndustrailRequestHistoryScreen(userId: widget.userData['id']),
                  ),
                );
              } else if (value == 'logout') {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (context) => const IndustrialLoginScreen()),
                  (route) => false,
                );
              }
            },
            itemBuilder: (BuildContext context) => [
              PopupMenuItem<String>(
                value: 'profile',
                child: Row(
                  children: [
                    Icon(Icons.person_outline, color: AppColors.primaryBlue),
                    const SizedBox(width: 8),
                    const Text('Profile'),
                  ],
                ),
              ),
              PopupMenuItem<String>(
                value: 'request_history',
                child: Row(
                  children: [
                    Icon(Icons.history, color: AppColors.primaryBlue),
                    const SizedBox(width: 8),
                    const Text('Request History'),
                  ],
                ),
              ),
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
        child: _screens[_selectedIndex],
      ),
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, child) {
          return Container(
            decoration: BoxDecoration(
              boxShadow: [
                BoxShadow(
                  color: AppColors.primaryBlue.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, -5),
                ),
              ],
            ),
            child: BottomNavigationBar(
              items: <BottomNavigationBarItem>[
                const BottomNavigationBarItem(
                  icon: Icon(Icons.home_outlined),
                  activeIcon: Icon(Icons.home),
                  label: 'Home',
                ),
                BottomNavigationBarItem(
                  icon: Stack(
                    children: [
                      const Icon(Icons.shopping_cart_outlined),
                      if (cart.itemCount > 0)
                        Positioned(
                          right: 0,
                          top: 0,
                          child: Container(
                            padding: const EdgeInsets.all(1),
                            decoration: BoxDecoration(
                              color: Colors.red,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            constraints: const BoxConstraints(
                              minWidth: 12,
                              minHeight: 12,
                            ),
                            child: Text(
                              '${cart.itemCount}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 8,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  ),
                  activeIcon: Icon(
                    Icons.shopping_cart,
                    shadows: [
                      Shadow(
                        color: AppColors.primaryBlue.withOpacity(0.3),
                        blurRadius: 8,
                      ),
                    ],
                  ),
                  label: 'Cart',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.message_outlined),
                  activeIcon: Icon(Icons.message),
                  label: 'Message',
                ),
              ],
              currentIndex: _selectedIndex,
              onTap: _onItemTapped,
              selectedItemColor: AppColors.primaryBlue,
              unselectedItemColor: AppColors.textSecondary,
              showUnselectedLabels: true,
              elevation: 0,
              backgroundColor: Colors.white,
            ),
          );
        },
      ),
    );
  }
} 