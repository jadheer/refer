import 'package:flutter/material.dart';
import 'package:forsat/presentation/Opportunities/opportunities_page.dart';
import 'package:forsat/presentation/Opportunities/opportunity_detail.dart';
import 'package:forsat/presentation/auth/account_page.dart';
import 'package:forsat/presentation/auth/sign_in_page.dart';
import 'package:forsat/presentation/auth/sign_up_page.dart';
import 'package:forsat/presentation/forum/questions_page.dart';
import 'package:forsat/presentation/home/home_page.dart';
import 'package:forsat/presentation/not_found/not_found.dart';
import 'package:forsat/router/route_constants.dart';

class Router {
  static Route<dynamic> onGenerateRoute(RouteSettings routeSettings) {
    switch (routeSettings.name) {
      case opportunitiesRoute:
        return MaterialPageRoute(builder: (_) => OpportunitiesPage());
      case opportunityDetailRoute:
        return MaterialPageRoute(
            settings: routeSettings, builder: (_) => OpportunityDetailPage());
      case homeRoute:
        return MaterialPageRoute(builder: (_) => HomePage());
      case signInRoute:
        return MaterialPageRoute(builder: (_) => SignInPage());
      case signUpRoute:
        return MaterialPageRoute(builder: (_) => SignUpPage());
      case AccountRoute:
        return MaterialPageRoute(builder: (_) => AccountPage());
      case QuestionsRoute:
        return MaterialPageRoute(builder: (_) => QuestionsPage());
      default:
        return MaterialPageRoute(builder: (_) => NotFound());
    }
  }
}
