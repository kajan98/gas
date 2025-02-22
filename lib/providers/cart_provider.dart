import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class CartProvider with ChangeNotifier {
  List<Map<String, dynamic>> _cartItems = [];
  List<Map<String, dynamic>> _industrialCartItems = [];
  bool _isIndustrial = false;

  List<Map<String, dynamic>> get cartItems => 
      _isIndustrial ? _industrialCartItems : _cartItems;

  set isIndustrial(bool value) {
    _isIndustrial = value;
    notifyListeners();
  }

  // Regular update for consumer (max 5)
  void updateQuantity(int index, bool increase) {
    if (increase && _cartItems[index]['quantity'] < 5) {
      _cartItems[index]['quantity']++;
    } else if (!increase && _cartItems[index]['quantity'] > 1) {
      _cartItems[index]['quantity']--;
    }
    notifyListeners();
  }

  // Industrial update (unlimited)
  void updateQuantity1(int index, bool increase) {
    if (_isIndustrial) {
      if (increase) {
        // No upper limit for industrial users
        _industrialCartItems[index]['quantity']++;
      } else if (!increase && _industrialCartItems[index]['quantity'] > 1) {
        _industrialCartItems[index]['quantity']--;
      }
    }
    notifyListeners();
  }

  void addToCart(Map<String, dynamic> item, BuildContext context) {
    final currentCart = _isIndustrial ? _industrialCartItems : _cartItems;
    
    final existingItemIndex = currentCart.indexWhere(
      (i) => i['pack_name'] == item['pack_name']
    );
    
    if (existingItemIndex != -1) {
      if (!_isIndustrial && currentCart[existingItemIndex]['quantity'] >= 5) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Maximum quantity of 5 per item reached'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 1),
          ),
        );
        return;
      }
      currentCart[existingItemIndex]['quantity']++;
    } else {
      Map<String, dynamic> newItem = {...item};
      newItem['quantity'] = 1;
      if (_isIndustrial) {
        _industrialCartItems.add(newItem);
      } else {
        _cartItems.add(newItem);
      }
    }
    
    notifyListeners();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Item added to cart successfully'),
        backgroundColor: Colors.green,
        duration: Duration(seconds: 1),
      ),
    );
  }

  void removeItem(int index) {
    if (_isIndustrial) {
      _industrialCartItems.removeAt(index);
    } else {
      _cartItems.removeAt(index);
    }
    notifyListeners();
  }

  double get totalPrice {
    final currentCart = _isIndustrial ? _industrialCartItems : _cartItems;
    return currentCart.fold(0.0, (sum, item) {
      double price = double.tryParse(item['max_retail_price'].toString()) ?? 0.0;
      int quantity = item['quantity'] ?? 0;
      return sum + (price * quantity);
    });
  }

  void clearCart() {
    if (_isIndustrial) {
      _industrialCartItems.clear();
    } else {
      _cartItems.clear();
    }
    notifyListeners();
  }

  int get itemCount => cartItems.length;
  
  bool get isEmpty => cartItems.isEmpty;
  
  bool containsItem(String packName) {
    return cartItems.any((item) => item['pack_name'] == packName);
  }
  
  int getItemQuantity(String packName) {
    final item = cartItems.firstWhere(
      (item) => item['pack_name'] == packName,
      orElse: () => {'quantity': 0},
    );
    return item['quantity'] ?? 0;
  }
} 
