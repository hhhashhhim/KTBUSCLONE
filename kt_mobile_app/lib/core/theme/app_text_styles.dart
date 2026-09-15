import 'package:flutter/material.dart';

import 'app_colors.dart';

abstract final class AppTextStyles {
  static const displayLarge = TextStyle(
    fontSize: 28,
    height: 1.15,
    fontWeight: FontWeight.w800,
    color: AppColors.foreground,
    letterSpacing: -0.5,
  );

  static const displaySmall = TextStyle(
    fontSize: 22,
    height: 1.2,
    fontWeight: FontWeight.w800,
    color: AppColors.foreground,
    letterSpacing: -0.25,
  );

  static const title = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w700,
    color: AppColors.foreground,
  );

  static const body = TextStyle(
    fontSize: 14,
    height: 1.45,
    color: AppColors.foreground,
  );

  static const caption = TextStyle(
    fontSize: 12,
    height: 1.35,
    color: AppColors.muted,
  );
}
