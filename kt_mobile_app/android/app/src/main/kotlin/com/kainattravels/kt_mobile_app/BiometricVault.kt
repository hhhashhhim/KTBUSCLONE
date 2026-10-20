package com.kainattravels.kt_mobile_app

import android.content.Context
import android.os.Build
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import java.security.KeyPairGenerator
import java.security.KeyStore
import java.security.spec.MGF1ParameterSpec
import javax.crypto.Cipher
import javax.crypto.spec.OAEPParameterSpec
import javax.crypto.spec.PSource

/** Only a per-use, strong biometric can decrypt the renewal credential. */
class BiometricVault(private val activity: FragmentActivity) {
    private val alias = "kt_passenger_biometric_v1"
    private val prefs = activity.getSharedPreferences(alias, Context.MODE_PRIVATE)
    private val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
    private var pending: MethodChannel.Result? = null
    private var prompt: BiometricPrompt? = null
    private val strong = BiometricManager.Authenticators.BIOMETRIC_STRONG

    fun handle(call: MethodCall, result: MethodChannel.Result) {
        try {
            when (call.method) {
                "status" -> result.success(mapOf(
                    "available" to available(),
                    "enabled" to prefs.contains("ciphertext"),
                    "label" to "Face / fingerprint"
                ))
                "write" -> {
                    if (pending != null) { result.error("busy", "Authentication is in progress.", null); return }
                    val secret = call.argument<String>("secret")
                    if (secret == null || !secret.matches(Regex("[a-f0-9]{96}"))) {
                        result.error("invalid", "Invalid quick login credential.", null); return
                    }
                    if (!available()) { result.error("unavailable", "Set up fingerprint or supported face unlock in phone settings.", null); return }
                    if (!store.containsAlias(alias)) createKey()
                    // Public-key encryption needs no authentication. The private key
                    // is non-exportable and requires a biometric for each decryption.
                    val cipher = cipher()
                    cipher.init(Cipher.ENCRYPT_MODE, store.getCertificate(alias).publicKey, padding())
                    val encrypted = cipher.doFinal(secret.toByteArray(Charsets.UTF_8))
                    if (!prefs.edit().putString("ciphertext", Base64.encodeToString(encrypted, Base64.NO_WRAP)).commit()) {
                        throw IllegalStateException("Storage unavailable")
                    }
                    result.success(null)
                }
                "read" -> unlock(result)
                "release" -> result.success(null) // No reusable authentication context.
                "clear" -> {
                    val callback = pending
                    pending = null
                    prompt?.cancelAuthentication()
                    callback?.error("cancelled", "Quick login cancelled.", null)
                    prefs.edit().clear().commit()
                    store.deleteEntry(alias)
                    result.success(null)
                }
                else -> result.notImplemented()
            }
        } catch (_: Exception) {
            result.error("invalidated", "Quick login is unavailable. Sign in with your password and enable it again.", null)
        }
    }

    private fun available(): Boolean = Build.VERSION.SDK_INT >= 28 &&
        BiometricManager.from(activity).canAuthenticate(strong) == BiometricManager.BIOMETRIC_SUCCESS

    private fun createKey() {
        val builder = KeyGenParameterSpec.Builder(alias, KeyProperties.PURPOSE_DECRYPT)
            .setKeySize(2048)
            .setDigests(KeyProperties.DIGEST_SHA256, KeyProperties.DIGEST_SHA1)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_RSA_OAEP)
            .setUserAuthenticationRequired(true)
            .setInvalidatedByBiometricEnrollment(true)
        if (Build.VERSION.SDK_INT >= 30) {
            builder.setUserAuthenticationParameters(0, KeyProperties.AUTH_BIOMETRIC_STRONG)
        } else {
            @Suppress("DEPRECATION")
            builder.setUserAuthenticationValidityDurationSeconds(-1)
        }
        KeyPairGenerator.getInstance(KeyProperties.KEY_ALGORITHM_RSA, "AndroidKeyStore").apply {
            initialize(builder.build())
        }.generateKeyPair()
    }

    private fun cipher() = Cipher.getInstance("RSA/ECB/OAEPPadding")
    private fun padding() = OAEPParameterSpec("SHA-256", "MGF1", MGF1ParameterSpec.SHA1, PSource.PSpecified.DEFAULT)

    private fun unlock(result: MethodChannel.Result) {
        if (pending != null) { result.error("busy", "Authentication is in progress.", null); return }
        if (!available()) { result.error("unavailable", "Biometrics are unavailable or temporarily locked. Use your password.", null); return }
        val encoded = prefs.getString("ciphertext", null)
        if (encoded == null) { result.success(null); return }
        val cipher = cipher()
        cipher.init(Cipher.DECRYPT_MODE, store.getKey(alias, null), padding())
        pending = result
        prompt = BiometricPrompt(activity, ContextCompat.getMainExecutor(activity),
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(auth: BiometricPrompt.AuthenticationResult) {
                    val callback = pending ?: return
                    pending = null
                    try {
                        val plaintext = auth.cryptoObject!!.cipher!!.doFinal(Base64.decode(encoded, Base64.NO_WRAP))
                        callback.success(String(plaintext, Charsets.UTF_8))
                        plaintext.fill(0)
                    } catch (_: Exception) {
                        callback.error("invalidated", "Sign in with your password and enable quick login again.", null)
                    }
                }
                override fun onAuthenticationError(code: Int, message: CharSequence) {
                    val callback = pending ?: return
                    pending = null
                    val cancelled = code in listOf(BiometricPrompt.ERROR_USER_CANCELED,
                        BiometricPrompt.ERROR_CANCELED, BiometricPrompt.ERROR_NEGATIVE_BUTTON)
                    callback.error(if (cancelled) "cancelled" else "unavailable",
                        if (cancelled) "Quick login cancelled." else "Biometrics are unavailable. Use your password.", null)
                }
            })
        val info = BiometricPrompt.PromptInfo.Builder()
            .setTitle("Kainat Travels quick login")
            .setSubtitle("Confirm your identity to sign in")
            .setAllowedAuthenticators(strong)
            .setNegativeButtonText("Use password")
            .build()
        prompt!!.authenticate(info, BiometricPrompt.CryptoObject(cipher))
    }
}
