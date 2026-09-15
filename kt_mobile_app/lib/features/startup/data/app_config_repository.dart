import '../../../core/network/api_client.dart';
import '../domain/app_config.dart';

class AppConfigRepository {
  AppConfigRepository(this._api);

  final ApiClient _api;

  Future<MobileAppConfig> fetch() async {
    final raw = await _api.get('/app/config');
    return MobileAppConfig.fromJson((raw as Map).cast<String, dynamic>());
  }
}
