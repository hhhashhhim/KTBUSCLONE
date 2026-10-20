import '../../core/network/api_client.dart';

class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    this.readAt,
    this.bookingId,
  });
  final int id;
  final String title;
  final String body;
  final DateTime createdAt;
  final DateTime? readAt;
  final int? bookingId;
  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: (json['id'] as num).toInt(),
        title: json['title'] as String,
        body: json['body'] as String,
        createdAt: DateTime.parse(json['created_at'] as String),
        readAt: json['read_at'] == null
            ? null
            : DateTime.parse(json['read_at'] as String),
        bookingId: (json['booking_id'] as num?)?.toInt(),
      );
}

class NotificationPage {
  const NotificationPage({
    this.items = const [],
    this.unread = 0,
    this.page = 1,
    this.lastPage = 1,
  });
  final List<AppNotification> items;
  final int unread, page, lastPage;
}

class NotificationRepository {
  NotificationRepository(this.api);
  final ApiClient api;
  Future<NotificationPage> load(int page) async {
    final data =
        (await api.get(
              '/notifications',
              queryParameters: {'paginated': 1, 'page': page, 'per_page': 20},
            ))
            as Map;
    return NotificationPage(
      items: (data['items'] as List)
          .map(
            (row) =>
                AppNotification.fromJson(Map<String, dynamic>.from(row as Map)),
          )
          .toList(),
      unread: (data['unread_count'] as num).toInt(),
      page: (data['pagination']['current_page'] as num).toInt(),
      lastPage: (data['pagination']['last_page'] as num).toInt(),
    );
  }

  Future<AppNotification> find(int id) async => AppNotification.fromJson(
    Map<String, dynamic>.from(await api.get('/notifications/$id') as Map),
  );
  Future<void> read(int id) async {
    await api.post('/notifications/$id/read');
  }

  Future<void> readThrough(int id) async {
    await api.post('/notifications/read-all', data: {'through_id': id});
  }
}
