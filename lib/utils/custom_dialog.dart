import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:gas/theme/app_colors.dart';

class CustomDialog {
  static Future<void> show({
    required BuildContext context,
    required String title,
    required String message,
    bool isError = false,
    bool isSuccess = false,
    bool isWarning = false,
    VoidCallback? onOkPressed,
  }) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black26,
                  blurRadius: 10,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: isError
                        ? AppColors.primaryBlue.withOpacity(0.1)
                        : isSuccess
                            ? Colors.green.withOpacity(0.1)
                            : isWarning
                                ? Colors.orange.withOpacity(0.1)
                                : AppColors.primaryBlue.withOpacity(0.1),
                  ),
                  child: Icon(
                    isError
                        ? Icons.close
                        : isSuccess
                            ? Icons.check_circle
                            : isWarning
                                ? Icons.warning_amber
                                : Icons.info,
                    size: 40,
                    color: isError
                        ? AppColors.primaryBlue
                        : isSuccess
                            ? Colors.green
                            : isWarning
                                ? Colors.orange
                                : AppColors.primaryBlue,
                  ),
                )
                    .animate()
                    .scale(duration: 400.ms, curve: Curves.easeOut)
                    .then()
                    .shake(duration: 400.ms),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                )
                    .animate()
                    .fadeIn(duration: 300.ms)
                    .moveY(begin: 10, end: 0),
                const SizedBox(height: 10),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[700],
                  ),
                )
                    .animate()
                    .fadeIn(duration: 300.ms)
                    .moveY(begin: 10, end: 0),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pop();
                      if (onOkPressed != null) {
                        onOkPressed();
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isError
                          ? AppColors.primaryBlue
                          : isSuccess
                              ? Colors.green
                              : isWarning
                                  ? Colors.orange
                                  : AppColors.primaryBlue,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
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
                )
                    .animate()
                    .fadeIn(duration: 300.ms)
                    .moveY(begin: 10, end: 0),
              ],
            ),
          )
              .animate()
              .scale(duration: 300.ms, curve: Curves.easeOut),
        );
      },
    );
  }
} 