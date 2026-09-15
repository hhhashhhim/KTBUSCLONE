import 'dart:io';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:in_app_update/in_app_update.dart';

import '../../../core/config/app_environment.dart';

final immediateUpdateProvider = Provider<Future<bool> Function()>((ref) {
  return () async {
    if (!Platform.isAndroid ||
        AppEnvironmentConfig.environment == AppEnvironment.development) {
      return false;
    }
    try {
      final info = await InAppUpdate.checkForUpdate();
      if ((info.updateAvailability == UpdateAvailability.updateAvailable ||
              info.updateAvailability ==
                  UpdateAvailability.developerTriggeredUpdateInProgress) &&
          info.immediateUpdateAllowed) {
        await InAppUpdate.performImmediateUpdate();
        // Starting/canceling Play's flow never removes the version gate.
        return true;
      }
    } catch (_) {
      // Sideloaded apps and devices without Play use the configured store link.
    }
    return false;
  };
});
