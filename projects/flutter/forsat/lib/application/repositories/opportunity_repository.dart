import 'package:dio/dio.dart';
import 'package:forsat/application/classes/common/pagination.dart';
import 'package:forsat/application/classes/errors/common_error.dart';
import 'package:forsat/application/classes/opportunity/opportunities.dart';
import 'package:forsat/application/classes/opportunity/opportunity.dart';
import 'package:forsat/application/forsat_api.dart';
import 'package:forsat/application/storage/localstorage.dart';
import 'package:forsat/application/storage/storage_keys.dart';

abstract class OpportunityRepository {
  // we need to fetch the opportunities
  Future<Opportunities> getAllOpportunities(int page);
}

class OpportunityRepositoryImpl implements OpportunityRepository {
  @override
  Future<Opportunities> getAllOpportunities(int page) async {
    try {
      final response = await ForsatApi.dio.get("/api/opportunity?page=$page",
          options: Options(headers: {
            'Authorization': "Bearer ${LocalStorage.getItem(TOKEN)}"
          }));

      List _temp = response.data['data'];
      var _meta = response.data['meta'];

      Pagination pagination = Pagination.fromJson(_meta);
      // print(pagination.lastPage);

      List<Opportunity> _opportunities = _temp
          .map((opportunity) => Opportunity.fromJson(opportunity))
          .toList();
      return Opportunities(
          pagination: pagination, opportunities: _opportunities);
    } on DioError catch (e) {
      throw showNetworkError(e);
    }
  }
}
