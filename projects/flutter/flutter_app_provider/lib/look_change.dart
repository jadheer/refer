import 'package:flutter/cupertino.dart';

class LookChange extends ChangeNotifier {
  int five = 0;
  int getResult() => five;

  void addFive() {
    five += 5;
    notifyListeners();
  }
}
