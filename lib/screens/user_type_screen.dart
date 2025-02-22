import 'package:flutter/material.dart';
import 'package:gas/screens/login_screen.dart';
import 'package:gas/screens/industrial/industrial_login_screen.dart';
import 'package:gas/theme/app_colors.dart';
import 'package:gas/utils/language_constants.dart';
import 'package:shared_preferences/shared_preferences.dart';

class UserTypeScreen extends StatefulWidget {
  const UserTypeScreen({super.key});

  @override
  State<UserTypeScreen> createState() => _UserTypeScreenState();
}

class _UserTypeScreenState extends State<UserTypeScreen> {
  String _currentLanguage = 'en';

  @override
  void initState() {
    super.initState();
    _loadLanguage();
  }

  Future<void> _loadLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _currentLanguage = prefs.getString('language') ?? 'en';
    });
  }

  Future<void> _changeLanguage(String languageCode) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('language', languageCode);
    setState(() {
      _currentLanguage = languageCode;
    });
  }

  String _getTranslatedText(String key) {
    return LanguageConstants.translations[_currentLanguage]?[key] ?? 
           LanguageConstants.translations['en']![key]!;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppColors.primaryBlue.withOpacity(0.1),
              Colors.white,
              AppColors.primaryBlue.withOpacity(0.05),
            ],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Language Selection Buttons
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _buildLanguageButton('English', 'en'),
                    const SizedBox(width: 10),
                    _buildLanguageButton('தமிழ்', 'ta'),
                    const SizedBox(width: 10),
                    _buildLanguageButton('සිංහල', 'si'),
                  ],
                ),
                const SizedBox(height: 40),
                Image.asset(
                  'Gas/assets/images/Logo.png',
                  height: 120,
                ),
                const SizedBox(height: 40),
                Text(
                  _getTranslatedText('selectUserType'),
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryBlue,
                    shadows: [
                      Shadow(
                        color: AppColors.primaryBlue.withOpacity(0.3),
                        offset: const Offset(1, 1),
                        blurRadius: 2,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 40),
                _buildUserTypeCard(
                  context,
                  title: _getTranslatedText('consumer'),
                  icon: Icons.person,
                  description: _getTranslatedText('consumerDesc'),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const LoginScreen(),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 20),
                _buildUserTypeCard(
                  context,
                  title: _getTranslatedText('industrial'),
                  icon: Icons.business,
                  description: _getTranslatedText('industrialDesc'),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const IndustrialLoginScreen(),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLanguageButton(String label, String languageCode) {
    final isSelected = _currentLanguage == languageCode;
    return ElevatedButton(
      onPressed: () => _changeLanguage(languageCode),
      style: ElevatedButton.styleFrom(
        backgroundColor: isSelected ? AppColors.primaryBlue : Colors.white,
        foregroundColor: isSelected ? Colors.white : AppColors.primaryBlue,
        elevation: isSelected ? 8 : 2,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: BorderSide(
            color: AppColors.primaryBlue,
            width: 1,
          ),
        ),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 14,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  Widget _buildUserTypeCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required String description,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 8,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(15),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(15),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  icon,
                  size: 32,
                  color: AppColors.primaryBlue,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: AppColors.primaryBlue,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      description,
                      style: TextStyle(
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_ios,
                color: AppColors.primaryBlue,
              ),
            ],
          ),
        ),
      ),
    );
  }
} 