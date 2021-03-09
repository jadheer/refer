class Pagination {
  final int currentPage;
  final int lastPage;

  Pagination.fromJson(Map<String, dynamic> jsonMap)
      : currentPage = jsonMap['current_page'] ?? 0,
        lastPage = jsonMap['last_page'] ?? 0;
}
