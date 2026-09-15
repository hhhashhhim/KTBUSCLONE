import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../config/app_environment.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient(this._tokenStorage)
    : _dio = Dio(
        BaseOptions(
          baseUrl: AppEnvironmentConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 20),
          sendTimeout: const Duration(seconds: 20),
          headers: const {'Accept': 'application/json'},
        ),
      ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            final hadToken = (await _tokenStorage.read())?.isNotEmpty == true;
            await _tokenStorage.clear();
            if (hadToken) _unauthorized.add(null);
          }
          handler.next(error);
        },
      ),
    );
    if (AppEnvironmentConfig.enableNetworkLogs && kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: false,
          responseBody: false,
          requestHeader: false,
          responseHeader: false,
          logPrint: (value) => debugPrint('[API] $value'),
        ),
      );
    }
  }

  final Dio _dio;
  final TokenStorage _tokenStorage;
  final StreamController<void> _unauthorized =
      StreamController<void>.broadcast();

  Stream<void> get unauthorized => _unauthorized.stream;

  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await _dio.get<dynamic>(
        path,
        queryParameters: queryParameters,
        cancelToken: cancelToken,
      );
      return _unwrap(response);
    } on DioException catch (error) {
      throw _map(error);
    }
  }

  Future<dynamic> post(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    try {
      return _unwrap(
        await _dio.post<dynamic>(path, data: data, cancelToken: cancelToken),
      );
    } on DioException catch (error) {
      throw _map(error);
    }
  }

  Future<dynamic> put(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    try {
      return _unwrap(
        await _dio.put<dynamic>(path, data: data, cancelToken: cancelToken),
      );
    } on DioException catch (error) {
      throw _map(error);
    }
  }

  Future<dynamic> delete(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    try {
      return _unwrap(
        await _dio.delete<dynamic>(path, data: data, cancelToken: cancelToken),
      );
    } on DioException catch (error) {
      throw _map(error);
    }
  }

  dynamic _unwrap(Response<dynamic> response) {
    final body = response.data;
    if (body is Map<String, dynamic> && body.containsKey('success')) {
      if (body['success'] != true) {
        throw ApiException(
          body['message']?.toString() ?? 'The request could not be completed.',
          statusCode: response.statusCode,
          errors: _parseErrors(body['errors']),
        );
      }
      return body['data'];
    }
    return body;
  }

  ApiException _map(DioException error) {
    final response = error.response;
    final body = response?.data;
    if (body is Map<String, dynamic>) {
      return ApiException(
        body['message']?.toString() ?? _fallback(error.type),
        statusCode: response?.statusCode,
        errors: _parseErrors(body['errors']),
      );
    }
    return ApiException(
      _fallback(error.type),
      statusCode: response?.statusCode,
    );
  }

  String _fallback(DioExceptionType type) => switch (type) {
    DioExceptionType.connectionTimeout ||
    DioExceptionType.sendTimeout ||
    DioExceptionType.receiveTimeout => 'The server took too long to respond.',
    DioExceptionType.connectionError =>
      'Unable to connect. Check your internet connection and try again.',
    DioExceptionType.cancel => 'The request was cancelled.',
    _ => 'Something went wrong. Please try again.',
  };

  Map<String, List<String>> _parseErrors(dynamic raw) {
    if (raw is! Map) return const {};
    return raw.map((key, value) {
      final messages = value is List
          ? value.map((item) => item.toString()).toList()
          : <String>[value.toString()];
      return MapEntry(key.toString(), messages);
    });
  }

  void dispose() => _unauthorized.close();
}
