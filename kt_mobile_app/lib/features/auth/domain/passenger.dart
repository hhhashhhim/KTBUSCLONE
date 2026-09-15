class PassengerAccount {
  const PassengerAccount({
    required this.id,
    required this.fullName,
    required this.mobile,
    this.email,
    this.cnic,
    this.gender,
    this.mobileVerified = false,
  });

  final int id;
  final String fullName;
  final String mobile;
  final String? email;
  final String? cnic;
  final String? gender;
  final bool mobileVerified;

  factory PassengerAccount.fromJson(Map<String, dynamic> json) =>
      PassengerAccount(
        id: (json['id'] as num).toInt(),
        fullName: (json['full_name'] ?? json['name'] ?? '').toString(),
        mobile: (json['mobile'] ?? json['contact'] ?? '').toString(),
        email: json['email']?.toString(),
        cnic: json['cnic']?.toString(),
        gender: json['gender']?.toString(),
        mobileVerified: json['mobile_verified'] == true,
      );
}

class PasswordResetSession {
  const PasswordResetSession({required this.mobile, required this.token});

  final String mobile;
  final String token;
}
