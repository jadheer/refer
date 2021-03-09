import 'package:flutter/material.dart';

class HomePage extends StatelessWidget {
  static const String id = 'home_page';
  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.of(context).size;
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Color(0xffe21d03),
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Row(
              children: [
                Icon(Icons.format_list_bulleted),
                SizedBox(
                  width: size.width * 0.05,
                ),
                /*Image.asset(
                  'assets/images/logo.png',
                  height: 150.0,
                ),*/
                Text(
                  'Company',
                  style: TextStyle(
                    fontFamily: 'Samantha',
                    fontSize: 30.0,
                  ),
                ),
              ],
            ),
            Row(
              children: [
                Icon(Icons.search),
                SizedBox(
                  width: size.width * 0.05,
                ),
                Icon(Icons.shopping_basket),
              ],
            ),
          ],
        ),
      ),
      body: Stack(
        children: [
          Positioned(
            left: 0,
            bottom: 0,
            child: Container(
              width: size.width,
              // color: Colors.green,
              height: 60,
              child: Stack(
                children: [
                  CustomPaint(
                    size: Size(size.width, 60),
                    painter: BNBCustomPainter(),
                  ),
                  Center(
                    child: Container(
                      height: 40,
                      width: 40,
                      child: FloatingActionButton(
                        onPressed: () {},
                        child: Icon(Icons.shopping_basket),
                        backgroundColor: Color(0xffe21d03),
                      ),
                    ),
                  ),
                  Container(
                    width: size.width,
                    height: 60,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        IconButton(
                          icon: Icon(Icons.home),
                          onPressed: () {},
                        ),
                        IconButton(
                          icon: Icon(Icons.category),
                          onPressed: () {},
                        ),
                        SizedBox(
                          width: size.width * 0.2,
                        ),
                        IconButton(
                          icon: Icon(Icons.search),
                          onPressed: () {},
                        ),
                        IconButton(
                          icon: Icon(Icons.account_circle),
                          onPressed: () {},
                        ),
                      ],
                    ),
                  )
                ],
              ),
            ),
          )
        ],
      ),
    );
  }
}

class BNBCustomPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    Paint paint = Paint()
      ..color = Color(0xffdedddd)
      ..style = PaintingStyle.fill;
    Path path = Path()..moveTo(0, 20);

    // path.quadraticBezierTo(size.width * 0.2, 0, size.width * 0.35, 0);
    // path.quadraticBezierTo(size.width * 0.4, 0, size.width * 0.4, 20);
    // path.arcToPoint(Offset(size.width * 0.6, 20),
    //     radius: Radius.circular(10.0), clockwise: false);
    // path.quadraticBezierTo(size.width * 0.6, 0, size.width * 0.65, 0);
    // path.quadraticBezierTo(size.width * 0.8, 0, size.width, 20);
    // path.lineTo(size.width, size.height);
    // path.lineTo(0, size.height);
    // path.close();

    path.lineTo(size.width * 0.3, 20);

    // path.quadraticBezierTo(size.width * 0.4, 20, size.width * 0.4, 15);
    // path.arcToPoint(Offset(size.width * 0.6, 15),
    //     radius: Radius.circular(10.0), clockwise: true);
    // path.quadraticBezierTo(size.width * 0.6, 20, size.width * 0.7, 20);

    path.quadraticBezierTo(size.width * 0.4, 20, size.width * 0.42, 15);
    path.lineTo(size.width * 0.48, 5);
    path.arcToPoint(Offset(size.width * 0.52, 5),
        radius: Radius.circular(30.0), clockwise: true);
    path.lineTo(size.width * 0.6, 17);
    path.quadraticBezierTo(size.width * 0.6, 20, size.width * 0.7, 20);

    path.lineTo(size.width, 20);
    path.lineTo(size.width, size.height);
    path.lineTo(0, size.height);
    path.close();

    canvas.drawShadow(path, Colors.black, 5, true);
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(CustomPainter oldDelegate) {
    return false;
  }
}
