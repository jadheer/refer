import 'package:flutter/cupertino.dart';
import 'package:forsat/application/classes/common/pagination.dart';
import 'package:forsat/application/classes/opportunity/opportunity.dart';

class Opportunities {
  final Pagination pagination;
  final List<Opportunity> opportunities;

  Opportunities({@required this.pagination, @required this.opportunities});
}
