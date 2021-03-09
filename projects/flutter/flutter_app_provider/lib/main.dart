import 'package:flutter/material.dart';
import 'package:flutter_app_provider/counter_model.dart';
import 'package:flutter_app_provider/look_change.dart';
import 'package:provider/provider.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Flutter Demo',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => CounterModel()),
          ChangeNotifierProvider(create: (_) => LookChange()),
        ],
        child: MyHomePage(),
      ),
    );
  }
}

class MyHomePage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    print("redreand");
    // CounterModel counterModel = Provider.of<CounterModel>(context);
    return Scaffold(
      body: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Center(
            child: Column(
              children: [
                Consumer<CounterModel>(
                  builder: (context, value, child) {
                    return Text(
                      "${Provider.of<CounterModel>(context).getCurrentCount()}",
                      style: TextStyle(
                        fontSize: 50,
                      ),
                    );
                  },
                ),
                SizedBox(
                  height: 50,
                ),
                GestureDetector(
                  onTap: () {
                    Provider.of<CounterModel>(context, listen: false)
                        .setAddCount();
                  },
                  child: Text("Add Count"),
                ),
                SizedBox(
                  height: 50,
                ),
                GestureDetector(
                  onTap: () {
                    Provider.of<CounterModel>(context, listen: false)
                        .setDecrementCount();
                  },
                  child: Text("Reduce Count"),
                ),
                SizedBox(
                  height: 50,
                ),
                Consumer<CounterModel>(
                  builder: (context, value, child) {
                    return Text(
                      "${Provider.of<LookChange>(context).getResult()}",
                      style: TextStyle(
                        fontSize: 50,
                      ),
                    );
                  },
                ),
                SizedBox(
                  height: 50,
                ),
                GestureDetector(
                  onTap: () {
                    Provider.of<LookChange>(context, listen: false).addFive();
                  },
                  child: Text("Add 5"),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
