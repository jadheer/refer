import 'package:flutter/material.dart';
import 'package:forsat/values/branding_color.dart';

showSnackBar({
  @required BuildContext context,
  @required String message,
  Color color,
}) {
  SnackBar mySnackBar = SnackBar(
    content: Text(message),
    backgroundColor: color ?? brandingColor,
  );
  Scaffold.of(context).showSnackBar(mySnackBar);
}
