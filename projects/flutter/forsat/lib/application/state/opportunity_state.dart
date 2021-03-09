import 'package:forsat/application/classes/common/pagination.dart';
import 'package:forsat/application/classes/opportunity/opportunities.dart';
import 'package:forsat/application/classes/opportunity/opportunity.dart';
import 'package:forsat/application/repositories/opportunity_repository.dart';
import 'package:forsat/utils/helper.dart';

class OpportunityState {
  final OpportunityRepository _opportunityRepository;

  OpportunityState(this._opportunityRepository);

  List<Opportunity> _opportunities = [];
  List<Opportunity> get opportunities => _opportunities;

  Pagination _pagination;

  bool _loading = false;
  bool get loading => _loading;

  Future getAllOpportunities() async {
    // print(fetchNext);

    if (Helper.paginationFetchNext(this._pagination).fetchNext) {
      _loading = true;
      Opportunities _newOpportunities =
          await _opportunityRepository.getAllOpportunities(
              Helper.paginationFetchNext(this._pagination).currentPage);
      _opportunities.addAll(_newOpportunities.opportunities);
      _pagination = _newOpportunities.pagination;
      _loading = false;

      // print(_pagination.lastPage);
    }
  }
}
