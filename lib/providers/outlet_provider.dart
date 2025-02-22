import 'package:flutter/foundation.dart';

class OutletProvider with ChangeNotifier {
  Map<String, dynamic>? selectedOutlet;

  void setSelectedOutlet(Map<String, dynamic> outlet) {
    selectedOutlet = outlet;
    notifyListeners();
  }
} 