import 'package:flutter/material.dart';

class OpportunityLinksWidget extends StatelessWidget {
  final String categoryName;
  final String views;
  final String deadline;

  const OpportunityLinksWidget(
      {Key key,
      @required this.categoryName,
      @required this.views,
      @required this.deadline})
      : super(key: key);

  @override
  Widget build(BuildContext context) {
    TextStyle _iconTextStyle = TextStyle(
      fontSize: 12,
      fontWeight: FontWeight.w600,
    );
    double _iconSize = 12;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        FlatButton(
          onPressed: () {},
          child: Row(
            children: [
              Icon(
                Icons.school,
                size: _iconSize,
              ),
              SizedBox(width: 5),
              Text(
                '$categoryName',
                style: _iconTextStyle,
              )
            ],
          ),
        ),
        Row(
          children: [
            Icon(
              Icons.remove_red_eye,
              size: _iconSize,
            ),
            SizedBox(width: 5),
            Text(
              '$views',
              style: _iconTextStyle,
            )
          ],
        ),
        FlatButton(
          onPressed: () {},
          child: Row(
            children: [
              Icon(
                Icons.share,
                size: _iconSize,
              ),
              SizedBox(width: 5),
              Text(
                'Share',
                style: _iconTextStyle,
              )
            ],
          ),
        ),
        Row(
          children: [
            Icon(
              Icons.event,
              size: _iconSize,
            ),
            SizedBox(width: 5),
            Text(
              '$deadline',
              style: _iconTextStyle,
            )
          ],
        ),
      ],
    );
  }
}
