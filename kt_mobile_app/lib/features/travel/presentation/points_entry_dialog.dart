import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class PointsEntryDialog extends StatefulWidget {
  const PointsEntryDialog({
    required this.selected,
    required this.available,
    super.key,
  });

  final int selected;
  final int available;

  @override
  State<PointsEntryDialog> createState() => _PointsEntryDialogState();
}

class _PointsEntryDialogState extends State<PointsEntryDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    final text = '${widget.selected}';
    _controller = TextEditingController.fromValue(
      TextEditingValue(
        text: text,
        selection: TextSelection(baseOffset: 0, extentOffset: text.length),
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _apply() {
    if (_formKey.currentState!.validate()) {
      Navigator.of(context).pop(int.parse(_controller.text));
    }
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: const Text('Use Kainat Points'),
    scrollable: true,
    content: Form(
      key: _formKey,
      child: TextFormField(
        controller: _controller,
        autofocus: true,
        keyboardType: TextInputType.number,
        textInputAction: TextInputAction.done,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        autovalidateMode: AutovalidateMode.onUserInteraction,
        decoration: InputDecoration(
          labelText: 'Points to use',
          helperText:
              '${widget.available} points available. Enter 0 to use none.',
          helperMaxLines: 2,
          errorMaxLines: 2,
        ),
        validator: (text) {
          final points = int.tryParse(text ?? '');
          if (points == null || points < 0) {
            return 'Enter a whole number of points.';
          }
          if (points > widget.available) {
            return 'You can use up to ${widget.available} points.';
          }
          return null;
        },
        onFieldSubmitted: (_) => _apply(),
      ),
    ),
    actions: [
      TextButton(
        onPressed: () => Navigator.of(context).pop(),
        child: const Text('Cancel'),
      ),
      FilledButton(onPressed: _apply, child: const Text('Apply')),
    ],
  );
}
