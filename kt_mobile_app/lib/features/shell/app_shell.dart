import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';

class AppShell extends StatelessWidget {
  const AppShell({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final path = GoRouterState.of(context).uri.path;
    final index = path.startsWith('/tickets')
        ? 1
        : path.startsWith('/search')
        ? 2
        : path.startsWith('/notifications')
        ? 3
        : path.startsWith('/profile')
        ? 4
        : 0;
    return Scaffold(
      body: child,
      bottomNavigationBar: _LovableBottomBar(
        selectedIndex: index,
        onSelected: (selected) {
          const routes = [
            '/home',
            '/tickets',
            '/search',
            '/notifications',
            '/profile',
          ];
          context.go(routes[selected]);
        },
      ),
    );
  }
}

class _LovableBottomBar extends StatelessWidget {
  const _LovableBottomBar({
    required this.selectedIndex,
    required this.onSelected,
  });

  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) => Container(
    // Keep a full 60 logical pixels for the navigation content in addition
    // to the device safe area. A fixed total height leaves too little room on
    // iPhones with a home indicator and makes the labels overflow.
    height: 60 + MediaQuery.paddingOf(context).bottom,
    decoration: const BoxDecoration(
      color: Color(0xF7FAFCFA),
      border: Border(top: BorderSide(color: AppColors.border)),
    ),
    child: SafeArea(
      top: false,
      child: Row(
        children: [
          _item(0, Icons.home_outlined, Icons.home_rounded, 'Home'),
          _item(
            1,
            Icons.confirmation_number_outlined,
            Icons.confirmation_number_rounded,
            'My Trips',
          ),
          Expanded(
            child: Transform.translate(
              offset: const Offset(0, -20),
              child: Center(
                child: InkWell(
                  customBorder: const CircleBorder(),
                  onTap: () => onSelected(2),
                  child: Container(
                    width: 62,
                    height: 62,
                    decoration: BoxDecoration(
                      gradient: AppGradients.search,
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.background, width: 4),
                      boxShadow: const [
                        BoxShadow(
                          color: Color(0x33063C3A),
                          blurRadius: 24,
                          offset: Offset(0, 8),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.search_rounded,
                      color: Colors.white,
                      size: 30,
                    ),
                  ),
                ),
              ),
            ),
          ),
          _item(
            3,
            Icons.notifications_none_rounded,
            Icons.notifications_rounded,
            'Alerts',
          ),
          _item(
            4,
            Icons.person_outline_rounded,
            Icons.person_rounded,
            'Account',
          ),
        ],
      ),
    ),
  );

  Widget _item(int index, IconData icon, IconData selectedIcon, String label) {
    final selected = selectedIndex == index;
    return Expanded(
      child: InkWell(
        onTap: () => onSelected(index),
        child: Padding(
          padding: const EdgeInsets.only(top: 8),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                selected ? selectedIcon : icon,
                size: 23,
                color: selected ? AppColors.primary : AppColors.muted,
              ),
              const SizedBox(height: 3),
              Text(
                label,
                style: TextStyle(
                  color: selected ? AppColors.primary : AppColors.muted,
                  fontSize: 10.5,
                  fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
