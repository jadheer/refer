import 'package:forsat/application/classes/auth/user.dart';

class Comment {
  final String comment;
  final User createdBy;

  // Comment(this.comment, this.createdBy);
  // factory Comment.fromJson(dynamic jsonMap) {
  //   return Comment(
  //     jsonMap['comment'] ?? "",
  //     jsonMap['createdBy'],
  //   );
  // }

  Comment.fromJson(Map<String, dynamic> jsonMap)
      : comment = jsonMap['comment'] ?? "",
        createdBy = User.fromJson(jsonMap['createdBy']);
}
