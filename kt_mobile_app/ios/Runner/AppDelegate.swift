import Flutter
import UIKit
import LocalAuthentication
import Security

@main
@objc class AppDelegate: FlutterAppDelegate {
  private let biometricVault = BiometricVault()
  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    GeneratedPluginRegistrant.register(with: self)
    if let controller = window?.rootViewController as? FlutterViewController {
      FlutterMethodChannel(name: "kt/biometric_vault", binaryMessenger: controller.binaryMessenger)
        .setMethodCallHandler(biometricVault.handle)
    }
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }
}

/// The Keychain enforces the current biometric set; no biometric data enters Dart.
private final class BiometricVault {
  private let service = "com.kainattravels.biometric.v1"
  private let enabledKey = "kt.biometric.enabled"
  private var context: LAContext?
  private var busy = false
  private var generation = 0

  init() {
    // Keychain items can survive reinstall. Never restore quick login after reinstall.
    if !UserDefaults.standard.bool(forKey: "kt.biometric.install") {
      SecItemDelete(query() as CFDictionary)
      UserDefaults.standard.set(false, forKey: enabledKey)
      UserDefaults.standard.set(true, forKey: "kt.biometric.install")
    }
  }

  private func query() -> [String: Any] {
    [kSecClass as String: kSecClassGenericPassword,
     kSecAttrService as String: service,
     kSecAttrAccount as String: "renewal"]
  }

  func handle(_ call: FlutterMethodCall, result: @escaping FlutterResult) {
    if call.method == "status" {
      let check = LAContext()
      let available = check.canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: nil)
      result(["available": available,
              "enabled": UserDefaults.standard.bool(forKey: enabledKey),
              "label": check.biometryType == .faceID ? "Face ID" : "Touch ID"])
      return
    }
    if call.method == "clear" {
      generation += 1
      context?.invalidate()
      context = nil
      SecItemDelete(query() as CFDictionary)
      UserDefaults.standard.set(false, forKey: enabledKey)
      result(nil)
      return
    }
    if call.method == "release" {
      context?.invalidate()
      context = nil
      result(nil)
      return
    }
    guard !busy else {
      result(FlutterError(code: "busy", message: "Authentication is in progress.", details: nil))
      return
    }
    switch call.method {
    case "read":
      let auth = LAContext()
      auth.localizedFallbackTitle = ""
      var item = query()
      item[kSecReturnData as String] = true
      item[kSecUseAuthenticationContext as String] = auth
      item[kSecUseOperationPrompt as String] = "Sign in to Kainat Travels"
      context = auth
      busy = true
      let operation = generation
      DispatchQueue.global(qos: .userInitiated).async {
        var data: CFTypeRef?
        let status = SecItemCopyMatching(item as CFDictionary, &data)
        DispatchQueue.main.async {
          self.busy = false
          guard operation == self.generation else {
            result(self.error(errSecUserCanceled))
            return
          }
          if status == errSecSuccess, let bytes = data as? Data,
             let secret = String(data: bytes, encoding: .utf8) {
            result(secret)
          } else {
            auth.invalidate()
            self.context = nil
            result(self.error(status))
          }
        }
      }
    case "write":
      guard let args = call.arguments as? [String: Any], let secret = args["secret"] as? String,
            secret.range(of: "^[a-f0-9]{96}$", options: .regularExpression) != nil else {
        result(FlutterError(code: "invalid", message: "Invalid quick login credential.", details: nil))
        return
      }
      guard let access = SecAccessControlCreateWithFlags(nil,
              kSecAttrAccessibleWhenPasscodeSetThisDeviceOnly, .biometryCurrentSet, nil) else {
        result(error(errSecNotAvailable))
        return
      }
      busy = true
      let operation = generation
      var item = query()
      // Reuse only the context from this unlock for the immediate rotation write.
      if let context = context { item[kSecUseAuthenticationContext as String] = context }
      DispatchQueue.global(qos: .userInitiated).async {
        let value = Data(secret.utf8)
        var status = SecItemUpdate(item as CFDictionary, [kSecValueData as String: value] as CFDictionary)
        if status == errSecItemNotFound {
          item[kSecValueData as String] = value
          item[kSecAttrAccessControl as String] = access
          item[kSecAttrSynchronizable as String] = false
          status = SecItemAdd(item as CFDictionary, nil)
        }
        DispatchQueue.main.async {
          self.busy = false
          self.context?.invalidate()
          self.context = nil
          guard operation == self.generation else {
            SecItemDelete(self.query() as CFDictionary)
            UserDefaults.standard.set(false, forKey: self.enabledKey)
            result(self.error(errSecUserCanceled))
            return
          }
          if status == errSecSuccess {
            UserDefaults.standard.set(true, forKey: self.enabledKey)
            result(nil)
          } else {
            result(self.error(status))
          }
        }
      }
    default: result(FlutterMethodNotImplemented)
    }
  }

  private func error(_ status: OSStatus) -> FlutterError {
    let cancelled = status == errSecUserCanceled
    let invalidated = status == errSecItemNotFound
    return FlutterError(code: cancelled ? "cancelled" : invalidated ? "invalidated" : "unavailable",
      message: cancelled ? "Quick login cancelled." :
        "Quick login is unavailable. Use your password, then enable it again.", details: nil)
  }
}
