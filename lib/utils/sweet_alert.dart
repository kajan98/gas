import 'package:flutter/material.dart';
import 'package:gas/theme/app_colors.dart';

class SweetAlert {
  static Future<void> show({
    required BuildContext context,
    required String title,
    required String content,
    bool isError = false,
    bool isSuccess = false,
    VoidCallback? onPressed,
  }) async {
    return showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          elevation: 0,
          backgroundColor: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.rectangle,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black26,
                  blurRadius: 10.0,
                  offset: const Offset(0.0, 10.0),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                CircleAvatar(
                  backgroundColor: isError 
                      ? AppColors.primaryBlue.withOpacity(0.1)
                      : isSuccess 
                          ? Colors.green.withOpacity(0.1)
                          : AppColors.primaryBlue.withOpacity(0.1),
                  radius: 45,
                  child: Icon(
                    isError 
                        ? Icons.close
                        : isSuccess 
                            ? Icons.check
                            : Icons.info_outline,
                    size: 40,
                    color: isError 
                        ? AppColors.primaryBlue
                        : isSuccess 
                            ? Colors.green
                            : AppColors.primaryBlue,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  content,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[800],
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop();
                      if (onPressed != null) {
                        onPressed();
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isError 
                          ? AppColors.primaryBlue
                          : isSuccess 
                              ? Colors.green
                              : AppColors.primaryBlue,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: const Text(
                      'OK',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
} 