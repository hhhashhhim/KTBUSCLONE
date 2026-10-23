package com.kainattravels.kt_mobile_app

import android.os.Build
import android.os.Bundle
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterFragmentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            splashScreen.setOnExitAnimationListener { splash ->
                // Start as soon as Flutter draws; never hold up startup checks.
                splash.iconView?.animate()
                    ?.scaleX(1.08f)
                    ?.scaleY(1.08f)
                    ?.alpha(0f)
                    ?.setDuration(220L)
                    ?.start()
                splash.animate()
                    .alpha(0f)
                    .setDuration(220L)
                    .withEndAction { splash.remove() }
                    .start()
            }
        }
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        val vault = BiometricVault(this)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "kt/biometric_vault")
            .setMethodCallHandler(vault::handle)
    }
}
