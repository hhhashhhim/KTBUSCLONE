import 'package:flutter/widgets.dart';

abstract final class AppRadius {
  static const sm = Radius.circular(10);
  static const md = Radius.circular(14);
  static const lg = Radius.circular(18);
  static const xl = Radius.circular(24);
  static const pill = Radius.circular(999);

  static const card = BorderRadius.all(Radius.circular(22));
  static const field = BorderRadius.all(Radius.circular(20));
  static const sheet = BorderRadius.vertical(top: xl);
  static const button = BorderRadius.all(pill);
}
