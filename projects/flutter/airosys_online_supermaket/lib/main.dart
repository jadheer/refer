import 'package:flutter/material.dart';
import 'package:airosys_online_supermaket/pages/welcome/welcome_page.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:airosys_online_supermaket/pages/home/home_page.dart';
import 'package:after_layout/after_layout.dart';
import 'package:airosys_online_supermaket/data/constants.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Flutter Demo',
      theme: ThemeData(),
      // home: WelcomePage(),
      home: Splash(),
      routes: {
        // WelcomePage.id: (context) => WelcomePage(),
        // HomePage.id: (context) => HomePage(),
      },
    );
  }
}

class Splash extends StatefulWidget {
  @override
  SplashState createState() => new SplashState();
}

class SplashState extends State<Splash> with AfterLayoutMixin<Splash> {
  Future checkFirstSeen() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    bool _seen = (prefs.getBool('seen') ?? false);

    if (_seen) {
      Navigator.of(context).pushReplacement(
          new MaterialPageRoute(builder: (context) => new HomePage()));
    } else {
      Navigator.of(context).pushReplacement(
          new MaterialPageRoute(builder: (context) => new WelcomePage()));
    }
  }

  @override
  void afterFirstLayout(BuildContext context) => checkFirstSeen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Image.asset(
          kLoaderGif,
          height: 125.0,
          width: 125.0,
        ),
      ),
    );
  }
}
