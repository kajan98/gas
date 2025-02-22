class Validators {
  static String? validateNIC(String? value) {
    if (value == null || value.isEmpty) {
      return 'Please enter NIC';
    }

    // Pattern for old NIC (9 digits + V/X) or new NIC (12 digits)
    final oldNICPattern = RegExp(r'^\d{9}[VvXx]$');
    final newNICPattern = RegExp(r'^\d{12}$');

    if (!oldNICPattern.hasMatch(value) && !newNICPattern.hasMatch(value)) {
      return 'Invalid NIC format (Should be 123456789V or 123456789012)';
    }

    return null;
  }
} 