import 'package:flutter/material.dart';

abstract final class AppShadows {
  static const card = [
    BoxShadow(color: Color(0x1420442E), blurRadius: 18, offset: Offset(0, 5)),
    BoxShadow(color: Color(0x0A20442E), blurRadius: 3, offset: Offset(0, 1)),
  ];

  static const elevated = [
    BoxShadow(color: Color(0x2920442E), blurRadius: 28, offset: Offset(0, 10)),
  ];
}
