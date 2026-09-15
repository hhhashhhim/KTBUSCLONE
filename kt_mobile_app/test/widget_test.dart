import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/config/app_environment.dart';

void main() {
  test('development API base URL is never empty', () {
    expect(AppEnvironmentConfig.apiBaseUrl, isNotEmpty);
  });
}
