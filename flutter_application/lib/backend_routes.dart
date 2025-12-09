import 'dart:io';
import 'package:flutter/foundation.dart';

class BackendRoutes {
  static String get baseUrl {
    const String url = 'https://deployback-production-a207.up.railway.app';
    debugPrint("[BackendRoutes] Using base URL: $url");
    return url;
  }

  // Auth endpoints
  static const String googleAuth = "/api/auth/google";
  static const String register = "/api/register";
  static const String login = "/api/login";
  static const String user = "/api/user";
  static const String logout = "/api/logout";
  static const String calendarEvents = "/api/calendar-events";
}