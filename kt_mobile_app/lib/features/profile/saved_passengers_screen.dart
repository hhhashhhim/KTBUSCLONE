import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/app_widgets.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';

class SavedPassengersScreen extends ConsumerWidget {
  const SavedPassengersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passengers = ref.watch(savedPassengersProvider);
    return Scaffold(
      appBar: const AppPageHeader(
        title: 'Saved passengers',
        subtitle: 'Use saved details during checkout',
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(savedPassengersProvider.future),
        child: passengers.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              SizedBox(height: MediaQuery.sizeOf(context).height * .15),
              AppStateView(
                icon: Icons.people_outline_rounded,
                title: 'Could not load passengers',
                message: error.toString(),
                actionLabel: 'Try again',
                onAction: () => ref.invalidate(savedPassengersProvider),
              ),
            ],
          ),
          data: (items) => items.isEmpty
              ? ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: [
                    SizedBox(height: MediaQuery.sizeOf(context).height * .15),
                    AppStateView(
                      icon: Icons.people_outline_rounded,
                      title: 'No saved passengers',
                      message:
                          'Save family or frequent traveller details for faster booking.',
                      actionLabel: 'Add passenger',
                      onAction: () => _openEditor(context, ref),
                    ),
                  ],
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  physics: const AlwaysScrollableScrollPhysics(),
                  itemCount: items.length,
                  separatorBuilder: (_, _) =>
                      const SizedBox(height: AppSpacing.sm),
                  itemBuilder: (context, index) => _PassengerCard(
                    passenger: items[index],
                    onEdit: () => _openEditor(context, ref, items[index]),
                    onDelete: () => _delete(context, ref, items[index]),
                  ),
                ),
        ),
      ),
      bottomNavigationBar: AppStickyAction(
        child: FilledButton.icon(
          onPressed: () => _openEditor(context, ref),
          icon: const Icon(Icons.person_add_alt_1_rounded),
          label: const Text('Add passenger'),
        ),
      ),
    );
  }

  Future<void> _openEditor(
    BuildContext context,
    WidgetRef ref, [
    SavedPassenger? passenger,
  ]) async {
    final changed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _SavedPassengerEditor(passenger: passenger),
    );
    if (changed == true) {
      ref.invalidate(savedPassengersProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              passenger == null ? 'Passenger saved.' : 'Passenger updated.',
            ),
          ),
        );
      }
    }
  }

  Future<void> _delete(
    BuildContext context,
    WidgetRef ref,
    SavedPassenger passenger,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Remove passenger?'),
        content: Text(
          '${passenger.fullName} will no longer appear during checkout.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;

    try {
      await ref
          .read(travelRepositoryProvider)
          .deleteSavedPassenger(passenger.id);
      ref.invalidate(savedPassengersProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Passenger removed.')));
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }
}

class _PassengerCard extends StatelessWidget {
  const _PassengerCard({
    required this.passenger,
    required this.onEdit,
    required this.onDelete,
  });

  final SavedPassenger passenger;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) => AppCard(
    padding: EdgeInsets.zero,
    onTap: onEdit,
    child: ListTile(
      contentPadding: const EdgeInsets.fromLTRB(14, 8, 6, 8),
      leading: const CircleAvatar(
        backgroundColor: AppColors.primarySoft,
        foregroundColor: AppColors.primary,
        child: Icon(Icons.person_outline_rounded),
      ),
      title: Text(
        passenger.fullName,
        style: const TextStyle(fontWeight: FontWeight.w800),
      ),
      subtitle: Text('${passenger.cnic}\n${passenger.mobile}'),
      isThreeLine: true,
      trailing: PopupMenuButton<String>(
        tooltip: 'Passenger options',
        onSelected: (value) => value == 'edit' ? onEdit() : onDelete(),
        itemBuilder: (_) => const [
          PopupMenuItem(
            value: 'edit',
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(Icons.edit_outlined),
              title: Text('Edit'),
            ),
          ),
          PopupMenuItem(
            value: 'delete',
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(Icons.delete_outline_rounded),
              title: Text('Remove'),
            ),
          ),
        ],
      ),
    ),
  );
}

class _SavedPassengerEditor extends ConsumerStatefulWidget {
  const _SavedPassengerEditor({this.passenger});

  final SavedPassenger? passenger;

  @override
  ConsumerState<_SavedPassengerEditor> createState() =>
      _SavedPassengerEditorState();
}

class _SavedPassengerEditorState extends ConsumerState<_SavedPassengerEditor> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _cnic;
  late final TextEditingController _mobile;
  late String _gender;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.passenger?.fullName);
    _cnic = TextEditingController(text: widget.passenger?.cnic);
    _mobile = TextEditingController(text: widget.passenger?.mobile);
    _gender = widget.passenger?.gender ?? 'male';
  }

  @override
  void dispose() {
    _name.dispose();
    _cnic.dispose();
    _mobile.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Padding(
    padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
    child: SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 38,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              widget.passenger == null ? 'Add passenger' : 'Edit passenger',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: AppSpacing.lg),
            TextFormField(
              controller: _name,
              textInputAction: TextInputAction.next,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Full name',
                prefixIcon: Icon(Icons.person_outline_rounded),
              ),
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Full name is required.'
                  : null,
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _cnic,
              textInputAction: TextInputAction.next,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'CNIC',
                hintText: '#####-#######-#',
                prefixIcon: Icon(Icons.badge_outlined),
              ),
              validator: (value) => _digits(value).length == 13
                  ? null
                  : 'Enter a valid 13-digit CNIC.',
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _mobile,
              textInputAction: TextInputAction.done,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Mobile number',
                prefixIcon: Icon(Icons.phone_outlined),
              ),
              validator: (value) {
                final length = _digits(value).length;
                return length >= 10 && length <= 15
                    ? null
                    : 'Enter a valid mobile number.';
              },
            ),
            const SizedBox(height: AppSpacing.md),
            DropdownButtonFormField<String>(
              initialValue: _gender,
              decoration: const InputDecoration(
                labelText: 'Gender',
                prefixIcon: Icon(Icons.person_pin_outlined),
              ),
              items: const [
                DropdownMenuItem(value: 'male', child: Text('Male')),
                DropdownMenuItem(value: 'female', child: Text('Female')),
                DropdownMenuItem(value: 'other', child: Text('Other')),
              ],
              onChanged: (value) => _gender = value ?? _gender,
            ),
            const SizedBox(height: AppSpacing.lg),
            FilledButton(
              onPressed: _saving ? null : _save,
              child: Text(_saving ? 'Saving…' : 'Save passenger'),
            ),
          ],
        ),
      ),
    ),
  );

  String _digits(String? value) => (value ?? '').replaceAll(RegExp(r'\D'), '');

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final repository = ref.read(travelRepositoryProvider);
      final passenger = widget.passenger;
      if (passenger == null) {
        await repository.createSavedPassenger(
          fullName: _name.text.trim(),
          cnic: _digits(_cnic.text),
          mobile: _digits(_mobile.text),
          gender: _gender,
        );
      } else {
        await repository.updateSavedPassenger(
          id: passenger.id,
          fullName: _name.text.trim(),
          cnic: _digits(_cnic.text),
          mobile: _digits(_mobile.text),
          gender: _gender,
        );
      }
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}
