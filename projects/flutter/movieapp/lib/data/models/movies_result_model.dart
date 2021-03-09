import 'movie_model.dart';

class MoviesResultModel {
  int page;
  List<MovieModel> movies;
  int totalPages;
  int totalResults;

  MoviesResultModel({this.movies});

  MoviesResultModel.fromJson(Map<String, dynamic> json) {
    // print(json);
    if (json['results'] != null) {
      movies = new List<MovieModel>();
      json['results'].forEach((v) {
        // print(v);
        movies.add(MovieModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = Map<String, dynamic>();
    if (this.movies != null) {
      data['movies'] = this.movies.map((v) => v.toJson()).toList();
    }
    return data;
  }
}
