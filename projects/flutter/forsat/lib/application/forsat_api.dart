import 'package:dio/dio.dart';
import 'package:forsat/base_url.dart';

class ForsatApi {
  static BaseOptions _baseOptions = BaseOptions(baseUrl: BASE_URL);

  // For unauthenticated api's
  static Dio dio = Dio(_baseOptions);

  // For authenticated api's
  static Dio dioAuth() {
    return Dio();
  }
}
