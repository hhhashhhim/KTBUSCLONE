import java.util.Base64
import java.util.Properties
import java.net.URI

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val uploadProperties = Properties()
val uploadPropertiesFile = rootProject.file("key.properties")
if (uploadPropertiesFile.exists()) {
    uploadPropertiesFile.inputStream().use { uploadProperties.load(it) }
}

val dartDefines = (project.findProperty("dart-defines") as? String).orEmpty()
    .split(",").filter { it.isNotBlank() }.associate {
        val entry = String(Base64.getDecoder().decode(it), Charsets.UTF_8)
        entry.substringBefore("=") to entry.substringAfter("=", "")
    }

val validateReleaseConfiguration = tasks.register("validateReleaseConfiguration") {
    doLast {
        val required = listOf("storeFile", "storePassword", "keyAlias", "keyPassword")
        check(required.all { !uploadProperties.getProperty(it).isNullOrBlank() }) {
            "Release signing is missing. Configure android/key.properties using key.properties.example."
        }
        check(rootProject.file(uploadProperties.getProperty("storeFile")).isFile) {
            "The release upload keystore does not exist. Check storeFile in android/key.properties."
        }
        check(dartDefines["APP_ENV"] == "production") {
            "Release builds require APP_ENV=production. Use dart_defines.production.json."
        }
        val api = runCatching { URI(dartDefines["API_BASE_URL"].orEmpty()) }.getOrNull()
        check(api != null && api.scheme == "https" && !api.host.isNullOrBlank() &&
            api.host !in listOf("localhost", "127.0.0.1", "10.0.2.2", "[::1]") &&
            api.userInfo == null && api.query == null && api.fragment == null &&
            api.path.trimEnd('/') == "/api/mobile/v1") {
            "Release builds require a public HTTPS API_BASE_URL ending in /api/mobile/v1."
        }
    }
}

tasks.configureEach {
    if (name == "preReleaseBuild") {
        dependsOn(validateReleaseConfiguration)
    }
}

android {
    namespace = "com.kainattravels.kt_mobile_app"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_11.toString()
    }

    buildFeatures {
        resValues = true
    }

    defaultConfig {
        applicationId = "com.kainattravels.kt_mobile_app"
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName

        // Initialize native Firebase before Dart starts for closed-app delivery.
        // Share the selected build's public configuration with FirebaseOptions.
        val firebaseResources = mapOf(
            "google_app_id" to "FIREBASE_ANDROID_APP_ID",
            "google_api_key" to "FIREBASE_ANDROID_API_KEY",
            "gcm_defaultSenderId" to "FIREBASE_MESSAGING_SENDER_ID",
            "project_id" to "FIREBASE_PROJECT_ID",
        )
        if (firebaseResources.values.all { !dartDefines[it].isNullOrBlank() }) {
            firebaseResources.forEach { (resource, define) ->
                resValue("string", resource, dartDefines.getValue(define))
            }
        }
    }

    signingConfigs {
        create("release") {
            keyAlias = uploadProperties.getProperty("keyAlias")
            keyPassword = uploadProperties.getProperty("keyPassword")
            storePassword = uploadProperties.getProperty("storePassword")
            storeFile = uploadProperties.getProperty("storeFile")?.let { rootProject.file(it) }
        }
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
        }
    }
}

flutter {
    source = "../.."
}

dependencies {
    implementation("androidx.biometric:biometric:1.1.0")
}
