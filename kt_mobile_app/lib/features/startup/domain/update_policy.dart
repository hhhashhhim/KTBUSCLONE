import 'app_config.dart';

bool requiresAppUpdate(String currentVersion, MobileAppConfig config) =>
    compareAppVersions(currentVersion, config.minimumSupportedVersion) < 0 ||
    (config.forceUpdate &&
        compareAppVersions(currentVersion, config.latestVersion) < 0);

int compareAppVersions(String left, String right) {
  List<int> parts(String value) => value
      .split(RegExp(r'[-+]'))
      .first
      .split('.')
      .map((part) => int.tryParse(part) ?? 0)
      .toList();
  final a = parts(left);
  final b = parts(right);
  for (var index = 0; index < 3; index++) {
    final result = (index < a.length ? a[index] : 0).compareTo(
      index < b.length ? b[index] : 0,
    );
    if (result != 0) return result;
  }
  return 0;
}
