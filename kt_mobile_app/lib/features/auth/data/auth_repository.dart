import '../../../core/network/api_client.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/passenger.dart';

class AuthRepository {
  AuthRepository(this._api, this._tokens);

  final ApiClient _api;
  final TokenStorage _tokens;

  Future<PassengerAccount> login({
    required String mobile,
    required String password,
  }) async {
    final data = _asMap(
      await _api.post(
        '/auth/login',
        data: {'mobile': mobile, 'password': password},
      ),
    );
    await _tokens.write(data['token'].toString());
    return PassengerAccount.fromJson(_asMap(data['passenger']));
  }

  Future<RegistrationResult> register({
    required String fullName,
    required String mobile,
    required String cnic,
    required String password,
    required String passwordConfirmation,
    String? email,
  }) async {
    final data = _asMap(
      await _api.post(
        '/auth/register',
        data: {
          'full_name': fullName,
          'mobile': mobile,
          'email': email,
          'cnic': cnic,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      ),
    );
    await _tokens.write(data['token'].toString());
    return RegistrationResult(
      passenger: PassengerAccount.fromJson(_asMap(data['passenger'])),
      deliveryMessage: data['verification_delivery_sent'] == false
          ? (data['verification_message'] as String? ??
                'Your account was created, but the code could not be sent. Please try Resend code.')
          : null,
    );
  }

  Future<PassengerAccount?> restore() async {
    final token = await _tokens.read();
    if (token == null || token.isEmpty) return null;
    try {
      return PassengerAccount.fromJson(_asMap(await _api.get('/profile')));
    } catch (_) {
      await _tokens.clear();
      return null;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      await _tokens.clear();
    }
  }

  Future<void> forgotPassword(String mobile) =>
      _api.post('/auth/forgot-password', data: {'mobile': mobile});

  Future<void> deleteAccount(String password) async {
    await _api.delete(
      '/account',
      data: {'password': password, 'confirmation': 'DELETE'},
    );
  }

  Future<void> clearLocalSession() => _tokens.clear();

  Future<PasswordResetSession> verifyPasswordResetOtp({
    required String mobile,
    required String code,
  }) async {
    final data = _asMap(
      await _api.post(
        '/auth/verify-reset-otp',
        data: {'mobile': mobile, 'code': code},
      ),
    );
    return PasswordResetSession(
      mobile: mobile,
      token: data['reset_token'].toString(),
    );
  }

  Future<void> resetPassword({
    required PasswordResetSession session,
    required String password,
    required String passwordConfirmation,
  }) => _api.post(
    '/auth/reset-password',
    data: {
      'mobile': session.mobile,
      'reset_token': session.token,
      'password': password,
      'password_confirmation': passwordConfirmation,
    },
  );

  Future<PassengerAccount> verifyOtp(String code) async {
    final data = _asMap(
      await _api.post('/auth/verify-otp', data: {'code': code}),
    );
    return PassengerAccount.fromJson(_asMap(data['passenger']));
  }

  Future<void> resendOtp() => _api.post('/auth/resend-otp');

  Future<PassengerAccount> updateProfile(Map<String, dynamic> values) async {
    return PassengerAccount.fromJson(
      _asMap(await _api.put('/profile', data: values)),
    );
  }

  Map<String, dynamic> _asMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return value.cast<String, dynamic>();
    throw const FormatException('Expected an object from the server.');
  }
}

class RegistrationResult {
  const RegistrationResult({required this.passenger, this.deliveryMessage});

  final PassengerAccount passenger;
  final String? deliveryMessage;
}
