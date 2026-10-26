import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../notifications/notification_providers.dart';
import '../startup/application/startup_controller.dart';

import '../../core/theme/app_colors.dart';

class AppShell extends ConsumerWidget {
  const AppShell({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
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
        unread:
            ref.watch(mobileAppConfigProvider)?.features.notifications == true
            ? ref.watch(notificationPageProvider(1)).value?.unread ?? 0
            : 0,
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
    this.unread = 0,
    required this.onSelected,
  });

  final int selectedIndex;
  final int unread;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) => Stack(
    // The raised search button stays inside the hit-test area. Reserve the
    // device inset separately and let scaled labels determine the bar height.
    children: [
      Positioned(
        top: 14,
        left: 0,
        right: 0,
        bottom: 0,
        child: DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF159447), Color(0xFF087433), Color(0xFF045326)],
              stops: [0, 0.5, 1],
            ),
            border: const Border(
              top: BorderSide(color: Color(0xFF64BC7E), width: 1.2),
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primaryDeep.withValues(alpha: 0.22),
                blurRadius: 20,
                offset: const Offset(0, -3),
              ),
            ],
          ),
        ),
      ),
      SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.only(top: 14),
          child: IntrinsicHeight(
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
                    offset: const Offset(0, -14),
                    child: Center(
                      child: Semantics(
                        label: 'Search trips',
                        button: true,
                        selected: selectedIndex == 2,
                        child: Container(
                          width: 64,
                          height: 64,
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: Color(0x1400551F),
                                blurRadius: 6,
                                offset: Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Material(
                            shape: const CircleBorder(
                              side: BorderSide(
                                color: Color(0xFFD1EBD7),
                                width: 1.2,
                              ),
                            ),
                            clipBehavior: Clip.antiAlias,
                            color: AppColors.primary,
                            child: InkWell(
                              customBorder: const CircleBorder(),
                              onTap: () => onSelected(2),
                              child: const Icon(
                                Icons.search_rounded,
                                color: Colors.white,
                                size: 29,
                              ),
                            ),
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
        ),
      ),
    ],
  );

  Widget _item(int index, IconData icon, IconData selectedIcon, String label) {
    final selected = selectedIndex == index;
    return Expanded(
      child: Semantics(
        selected: selected,
        button: true,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => onSelected(index),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                Badge(
                  isLabelVisible: index == 3 && unread > 0,
                  backgroundColor: AppColors.gold,
                  textColor: AppColors.foreground,
                  label: Text(unread > 99 ? '99+' : '$unread'),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    curve: Curves.easeOut,
                    width: 48,
                    height: 30,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(11),
                      color: selected ? const Color(0xFF248D4B) : null,
                      border: Border.all(
                        color: selected
                            ? const Color(0xFF79C58F)
                            : Colors.transparent,
                      ),
                    ),
                    child: Icon(
                      selected ? selectedIcon : icon,
                      size: 23,
                      color: selected ? Colors.white : const Color(0xFFD0EAD8),
                    ),
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  label,
                  style: TextStyle(
                    color: selected ? Colors.white : const Color(0xFFD0EAD8),
                    fontSize: 10.5,
                    fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
