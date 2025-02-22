class ApiConstants {
  // Base URL
  static const String baseUrl = 'http://192.168.1.31/Gas/Gas';
   static const String baseUrl1 = 'http://192.168.1.31/Gas';
/// run in emulator
  //    static const String baseUrl = 'http://10.0.2.2/Gas/Gas';
  //  static const String baseUrl1 = 'http://10.0.2.2/Gas';
  // API Endpoints
  static const String loginEndpoint = '$baseUrl/consumer_login.php';
  static const String registerEndpoint = '$baseUrl/register_consumer.php';
  static const String updateProfileEndpoint = '$baseUrl/update_profile.php';
  static const String IndustrialUserRegisterEndpoint = '$baseUrl/industrial_register.php';
  
  static const String litroGasPacksEndpoint = '$baseUrl1/api/litro_gas_packs.php';
  static const String getOutletsEndpoint = '$baseUrl1/api/get_outlets.php';
  static const String getStockEndpoint= '$baseUrl1/api/get_stock.php';
  static const String submitComplaintEndpoint = '$baseUrl1/api/submit_complaint.php';
  static const String submitrequestEndpoint = '$baseUrl1/api/submit_request.php';
  static const String getRequestsEndpoint = '$baseUrl1/api/get_requests.php';
  static const String sendVerificationCodeEndpoint = '$baseUrl/send_verification_code.php';
  static const String verifyCodeEndpoint = '$baseUrl/verify_code.php';
  static const String resetPasswordEndpoint = '$baseUrl/reset_password.php';
  static const String submitIndustrialRequestEndpoint = '$baseUrl1/api/submit_industrial_request.php';
  static const String updateIndustrialUserEndpoint = '$baseUrl1/api/update_industrial_user.php';
  static const String getIndustrialRequestsEndpoint = '$baseUrl1/api/get_industrial_requests.php';

  
  // Other API related constants can be added here
} 