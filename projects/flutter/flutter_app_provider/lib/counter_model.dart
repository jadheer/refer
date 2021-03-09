import 'package:flutter/material.dart';

class CounterModel extends ChangeNotifier {
  int _count = 0;
  int getCurrentCount() => _count;

  void setAddCount() {
    _count++;
    notifyListeners();
  }

  void setDecrementCount() {
    _count--;
    notifyListeners();
  }
}
