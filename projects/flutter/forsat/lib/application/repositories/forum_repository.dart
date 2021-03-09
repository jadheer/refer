import 'package:dio/dio.dart';
import 'package:forsat/application/classes/common/pagination.dart';
import 'package:forsat/application/classes/errors/common_error.dart';
import 'package:forsat/application/classes/forum/question.dart';
import 'package:forsat/application/classes/forum/questions.dart';
import 'package:forsat/application/forsat_api.dart';
import 'package:forsat/application/storage/localstorage.dart';
import 'package:forsat/application/storage/storage_keys.dart';

abstract class ForumRepository {
  Future<Questions> getAllQuestions(int page);
}

class ForumRepositoryImpl implements ForumRepository {
  @override
  Future<Questions> getAllQuestions(int page) async {
    try {
      final response = await ForsatApi.dio.get("/api/questions?page=$page",
          options: Options(headers: {
            'Authorization': "Bearer ${LocalStorage.getItem(TOKEN)}"
          }));

      List _data = response.data['data'];
      var _meta = response.data['meta'];

      // print(response.data['data']);

      Pagination pagination = Pagination.fromJson(_meta);
      // print(pagination.lastPage);

      List<Question> _questions =
          _data.map((question) => Question.fromJson(question)).toList();

      return Questions(pagination: pagination, questions: _questions);
    } on DioError catch (e) {
      throw showNetworkError(e);
    }
  }
}
